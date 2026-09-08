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
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\ToggleUserActiveCommand;
use Webware\UserManager\Http\Admin\Middleware\ProcessToggleUserActiveMiddleware;

use function assert;

#[CoversClass(ProcessToggleUserActiveMiddleware::class)]
#[CoversMethod(ProcessToggleUserActiveMiddleware::class, '__construct')]
#[CoversMethod(ProcessToggleUserActiveMiddleware::class, 'processPost')]
final class ProcessToggleUserActiveMiddlewareTest extends TestCase
{
    #[Test]
    public function dispatchesToggleCommandAndForwardsResult(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->with($this->callback(
                static fn($command): bool => $command instanceof ToggleUserActiveCommand && 42 === $command->id,
            ))
            ->willReturnCallback(static function (MessageInterface $message): CommandResult {
                assert($message instanceof ToggleUserActiveCommand, description: 'Expected a ToggleUserActiveCommand');

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

        $middleware = new ProcessToggleUserActiveMiddleware(messageBus: $bus);

        $request = new ServerRequest()->withMethod('POST')
            ->withAttribute('id', '42');

        $response = $middleware->process($request, $handler);

        self::assertInstanceOf(EmptyResponse::class, $response);
        self::assertInstanceOf(CommandResult::class, $capturedRequest?->getAttribute(CommandResult::class));
    }

    #[Test]
    public function fallsBackToZeroWhenIdIsInvalid(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->with($this->callback(
                static fn($command): bool => $command instanceof ToggleUserActiveCommand && 0 === $command->id,
            ))
            ->willReturnCallback(static function (MessageInterface $message): CommandResult {
                assert($message instanceof ToggleUserActiveCommand, description: 'Expected a ToggleUserActiveCommand');

                return new CommandResult($message, MessageStatus::Success, null);
            });

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new EmptyResponse());

        $middleware = new ProcessToggleUserActiveMiddleware(messageBus: $bus);

        $request = new ServerRequest()->withMethod('POST')
            ->withAttribute('id', 'not-an-integer');

        $response = $middleware->process($request, $handler);

        self::assertInstanceOf(EmptyResponse::class, $response);
    }
}
