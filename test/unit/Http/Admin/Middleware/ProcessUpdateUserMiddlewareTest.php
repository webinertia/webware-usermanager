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
    public function dispatchesCommandAndStoresResult(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->with($this->callback(
                static fn($command): bool => (
                    $command instanceof UpdateUserCommand
                    && 42 === $command->id
                    && 'Jane' === $command->firstName
                    && 'Member' === $command->roleId
                    && true === $command->active
                ),
            ))
            ->willReturnCallback(static function (MessageInterface $message): CommandResult {
                assert($message instanceof UpdateUserCommand, description: 'Expected an UpdateUserCommand');

                return new CommandResult($message, MessageStatus::Success, null);
            });

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
            ->withAttribute('id', '42')
            ->withParsedBody([
                'firstName' => 'Jane',
                'lastName'  => 'Doe',
                'email'     => 'jane@example.com',
                'roleId'    => 'Member',
                'active'    => '1',
            ]);

        $response = $middleware->processPatch($request, $handler);

        self::assertInstanceOf(EmptyResponse::class, $response);
        self::assertInstanceOf(CommandResult::class, $capturedRequest?->getAttribute(CommandResult::class));
    }

    #[Test]
    public function storesFailureResultWithoutNotifying(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturnCallback(
            static function (MessageInterface $message): CommandResult {
                assert($message instanceof UpdateUserCommand, description: 'Expected an UpdateUserCommand');

                return new CommandResult($message, MessageStatus::Failure, null);
            },
        );

        $messenger = $this->createMock(SystemMessengerInterface::class);
        $messenger->expects($this->never())->method('success');
        $messenger->expects($this->never())->method('danger');
        $messenger->expects($this->never())->method('warning');

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
                'roleId'    => 'Member',
                'active'    => '1',
            ]);

        $response = $middleware->processPatch($request, $handler);

        self::assertInstanceOf(EmptyResponse::class, $response);
        $result = $capturedRequest?->getAttribute(CommandResult::class);
        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(MessageStatus::Failure, $result?->getStatus());
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
