<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Admin\Middleware;

use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\ServerRequest;
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
use Webware\UserManager\Command\UpdateUserCommand;
use Webware\UserManager\Http\Admin\Middleware\ProcessUpdateUserMiddleware;
use WebwareTest\UserManager\Support\InputFilterHelper;

use function assert;
use function is_string;

#[CoversClass(ProcessUpdateUserMiddleware::class)]
#[CoversMethod(ProcessUpdateUserMiddleware::class, '__construct')]
#[CoversMethod(ProcessUpdateUserMiddleware::class, 'processPatch')]
final class ProcessUpdateUserMiddlewareTest extends TestCase
{
    #[Test]
    public function dispatchesCommandAndNotifiesOnSuccess(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->with($this->callback(
                static fn($command): bool => (
                    $command instanceof UpdateUserCommand
                    && 42 === $command->id
                    && 'Jane' === $command->firstName
                    && ['member'] === $command->roleId
                    && true === $command->active
                ),
            ))
            ->willReturnCallback(static function (MessageInterface $message): CommandResult {
                assert($message instanceof UpdateUserCommand, description: 'Expected an UpdateUserCommand');

                return new CommandResult($message, MessageStatus::Success, null);
            });

        $messenger = $this->createMock(SystemMessengerInterface::class);
        $messenger->expects($this->once())->method('success')->with('User updated.', 0, true);

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

        $middleware = new ProcessUpdateUserMiddleware(
            messageBus: $bus,
            filter    : InputFilterHelper::updateUserDataFilter(),
        );

        $request = new ServerRequest()->withMethod('PATCH')
            ->withAttribute(SystemMessengerInterface::class, $messenger)
            ->withAttribute('id', '42')
            ->withParsedBody([
                'firstName' => 'Jane',
                'lastName'  => 'Doe',
                'email'     => 'jane@example.com',
                'roleId'    => ['member'],
                'active'    => '1',
            ]);

        $response = $middleware->processPatch($request, $handler);

        self::assertInstanceOf(EmptyResponse::class, $response);
        self::assertInstanceOf(CommandResult::class, $capturedRequest?->getAttribute(CommandResult::class));
    }

    #[Test]
    public function notifiesFailureWhenCommandFails(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturnCallback(
            static function (MessageInterface $message): CommandResult {
                assert($message instanceof UpdateUserCommand, description: 'Expected an UpdateUserCommand');

                return new CommandResult($message, MessageStatus::Failure, null);
            },
        );

        $messenger = $this->createMock(SystemMessengerInterface::class);
        $messenger->expects($this->once())
            ->method('danger')
            ->with('User could not be updated. Please try again.', 0, true);

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

        $middleware = new ProcessUpdateUserMiddleware(
            messageBus: $bus,
            filter    : InputFilterHelper::updateUserDataFilter(),
        );

        $request = new ServerRequest()->withMethod('PATCH')
            ->withAttribute(SystemMessengerInterface::class, $messenger)
            ->withAttribute('id', '42')
            ->withParsedBody([
                'firstName' => 'Jane',
                'lastName'  => 'Doe',
                'email'     => 'jane@example.com',
                'roleId'    => ['member'],
                'active'    => '1',
            ]);

        $response = $middleware->processPatch($request, $handler);

        self::assertInstanceOf(EmptyResponse::class, $response);
        self::assertInstanceOf(CommandResult::class, $capturedRequest?->getAttribute(CommandResult::class));
    }

    #[Test]
    public function warnsAndPassesThroughWhenValidationFails(): void
    {
        $messenger = $this->createMock(SystemMessengerInterface::class);
        $messenger->expects($this->once())
            ->method('warning')
            ->with($this->callback(is_string(...)));

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn(new EmptyResponse());

        $middleware = new ProcessUpdateUserMiddleware(
            messageBus: $this->createStub(MessageBusInterface::class),
            filter    : InputFilterHelper::updateUserDataFilter(),
        );

        $request = new ServerRequest()->withMethod('PATCH')
            ->withAttribute(SystemMessengerInterface::class, $messenger)
            ->withAttribute('id', '42')
            ->withParsedBody(['email' => 'not-an-email']);

        $response = $middleware->processPatch($request, $handler);

        self::assertInstanceOf(EmptyResponse::class, $response);
    }
}
