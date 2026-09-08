<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Middleware;

use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Message\SystemMessengerInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\CreateUserCommand;
use Webware\UserManager\Middleware\RegistrationMiddleware;
use WebwareTest\UserManager\Support\InputFilterHelper;

use function assert;
use function bin2hex;
use function is_array;
use function random_bytes;

#[CoversClass(RegistrationMiddleware::class)]
#[CoversMethod(RegistrationMiddleware::class, '__construct')]
#[CoversMethod(RegistrationMiddleware::class, 'process')]
final class RegistrationMiddlewareTest extends TestCase
{
    #[Test]
    public function dispatchesCommandAndForwardsResultOnSuccess(): void
    {
        $password = bin2hex(random_bytes(16));

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->willReturnCallback(static function (MessageInterface $message): CommandResult {
                assert($message instanceof CreateUserCommand, description: 'Expected a CreateUserCommand');

                return new CommandResult($message, MessageStatus::Success, null);
            });

        $messenger = $this->createMock(SystemMessengerInterface::class);
        $messenger->expects($this->once())
            ->method('success')
            ->with('Registration successful! Please check your email to verify your account.', 1, false);

        $capturedRequest = null;
        $handler         = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(static function (ServerRequestInterface $request) use (
                &$capturedRequest,
            ): ResponseInterface {
                $capturedRequest = $request;

                return new EmptyResponse();
            });

        $middleware = new RegistrationMiddleware(
            messageBus: $bus,
            template  : $this->createStub(TemplateRendererInterface::class),
            filter    : InputFilterHelper::registrationDataFilter(),
        );

        $request = new ServerRequest()->withMethod('POST')
            ->withAttribute(SystemMessengerInterface::class, $messenger)
            ->withParsedBody([
                'firstName'           => 'Jane',
                'lastName'            => 'Doe',
                'email'               => 'jane@example.com',
                'passwordHash'        => $password,
                'confirmPasswordHash' => $password,
            ]);

        $response = $middleware->process($request, $handler);

        self::assertInstanceOf(EmptyResponse::class, $response);
        self::assertInstanceOf(CommandResult::class, $capturedRequest?->getAttribute(CommandResult::class));
    }

    #[Test]
    public function rendersErrorsWhenValidationFails(): void
    {
        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with(
                'user::registration',
                $this->callback(static fn(array $data): bool => is_array($data['errors'] ?? null)),
            )
            ->willReturn('<form>');

        $middleware = new RegistrationMiddleware(
            messageBus: $this->createStub(MessageBusInterface::class),
            template  : $template,
            filter    : InputFilterHelper::registrationDataFilter(),
        );

        $request = new ServerRequest()->withMethod('POST')
            ->withParsedBody(['email' => 'jane@example.com']);

        $response = $middleware->process($request, $this->createStub(RequestHandlerInterface::class));

        self::assertInstanceOf(HtmlResponse::class, $response);
        self::assertSame(422, $response->getStatusCode());
    }

    #[Test]
    public function rendersServerErrorOnCommandFailure(): void
    {
        $password = bin2hex(random_bytes(16));

        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturnCallback(
            static function (MessageInterface $message): CommandResult {
                assert($message instanceof CreateUserCommand, description: 'Expected a CreateUserCommand');

                return new CommandResult($message, MessageStatus::Failure, 'Email already registered.');
            },
        );

        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::registration', ['errors' => ['Email already registered.']])
            ->willReturn('<form>');

        $middleware = new RegistrationMiddleware(
            messageBus: $bus,
            template  : $template,
            filter    : InputFilterHelper::registrationDataFilter(),
        );

        $request = new ServerRequest()->withMethod('POST')
            ->withParsedBody([
                'firstName'           => 'Jane',
                'lastName'            => 'Doe',
                'email'               => 'jane@example.com',
                'passwordHash'        => $password,
                'confirmPasswordHash' => $password,
            ]);

        $response = $middleware->process($request, $this->createStub(RequestHandlerInterface::class));

        self::assertInstanceOf(HtmlResponse::class, $response);
        self::assertSame(500, $response->getStatusCode());
    }
}
