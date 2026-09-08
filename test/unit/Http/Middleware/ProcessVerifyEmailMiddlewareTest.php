<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Middleware;

use DateTimeImmutable;
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
use Webware\MessageBus\Query\QueryResult;
use Webware\MessageBus\ResultInterface;
use Webware\UserManager\Command\ActivateUserCommand;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Http\Middleware\ProcessVerifyEmailMiddleware;
use Webware\UserManager\Http\RequestHandler\VerifyEmailHandler;
use Webware\UserManager\Query\FetchUserByVerificationToken;

use function bin2hex;
use function random_bytes;

#[CoversClass(ProcessVerifyEmailMiddleware::class)]
#[CoversMethod(ProcessVerifyEmailMiddleware::class, '__construct')]
#[CoversMethod(ProcessVerifyEmailMiddleware::class, 'process')]
final class ProcessVerifyEmailMiddlewareTest extends TestCase
{
    #[Test]
    public function activatesUserAndAttachesCommandResult(): void
    {
        $user          = new User(id: 3);
        $commandResult = new CommandResult(new ActivateUserCommand(id: 3), MessageStatus::Success, 1);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->exactly(2))
            ->method('handle')
            ->willReturnCallback(
                static fn(MessageInterface $message): ResultInterface => $message
                    instanceof FetchUserByVerificationToken
                        ? new QueryResult($message, MessageStatus::Success, $user)
                        : $commandResult,
            );

        $messenger = $this->createMock(SystemMessengerInterface::class);
        $messenger->expects($this->once())
            ->method('success')
            ->with('Email verified! You may now sign in.', 1, false);

        $request = $this->process(
            bus: $bus,
            request: new ServerRequest()->withAttribute('token', 'x')
                ->withAttribute(SystemMessengerInterface::class, $messenger),
        );

        static::assertSame($commandResult, $request->getAttribute(CommandResult::class));
    }

    #[Test]
    public function setsErrorOnEmptyToken(): void
    {
        $request = $this->process(
            bus    : $this->createStub(MessageBusInterface::class),
            request: new ServerRequest(),
        );

        static::assertSame(
            ['error' => 'Invalid verification link.'],
            $request->getAttribute(VerifyEmailHandler::class),
        );
    }

    #[Test]
    public function setsErrorOnUnknownToken(): void
    {
        $token = bin2hex(random_bytes(16));

        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')
            ->willReturn(new QueryResult(
                new FetchUserByVerificationToken(token: $token),
                MessageStatus::Failure,
                null,
            ));

        $request = $this->process(
            bus: $bus,
            request: new ServerRequest()->withAttribute('token', $token),
        );

        static::assertSame(
            ['error' => 'Invalid or already used verification link.'],
            $request->getAttribute(VerifyEmailHandler::class),
        );
    }

    #[Test]
    public function setsExpiredErrorWhenTokenTooOld(): void
    {
        $token = bin2hex(random_bytes(16));
        $user  = new User(
            id            : 3,
            tokenCreatedAt: new DateTimeImmutable('-2 hours'),
        );

        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')
            ->willReturn(new QueryResult(
                new FetchUserByVerificationToken(token: $token),
                MessageStatus::Success,
                $user,
            ));

        $request = $this->process(
            bus: $bus,
            request: new ServerRequest()->withAttribute('token', $token),
        );

        static::assertSame(
            ['error' => 'Your verification link has expired.', 'expired' => true],
            $request->getAttribute(VerifyEmailHandler::class),
        );
    }

    #[Test]
    public function treatsAgeEqualToTtlAsNotExpired(): void
    {
        $token         = bin2hex(random_bytes(16));
        $user          = new User(id: 3);
        $commandResult = new CommandResult(new ActivateUserCommand(id: 3), MessageStatus::Success, 1);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->exactly(2))
            ->method('handle')
            ->willReturnCallback(
                static fn(MessageInterface $message): ResultInterface => $message
                    instanceof FetchUserByVerificationToken
                        ? new QueryResult($message, MessageStatus::Success, $user)
                        : $commandResult,
            );

        $request = $this->process(
            bus     : $bus,
            tokenTtl: 0,
            request : new ServerRequest()->withAttribute('token', $token),
        );

        static::assertSame($commandResult, $request->getAttribute(CommandResult::class));
    }

    private function process(
        MessageBusInterface $bus,
        ServerRequestInterface $request,
        int $tokenTtl = 3600,
    ): ServerRequestInterface {
        $capturedRequest = null;
        $handler         = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(static function (ServerRequestInterface $req) use (
                &$capturedRequest,
            ): ResponseInterface {
                $capturedRequest = $req;

                return new EmptyResponse();
            });

        new ProcessVerifyEmailMiddleware(
            messageBus: $bus,
            tokenTtl  : $tokenTtl,
        )->process($request, $handler);

        return $capturedRequest;
    }
}
