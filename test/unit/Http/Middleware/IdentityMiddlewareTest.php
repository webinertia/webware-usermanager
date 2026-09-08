<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Middleware;

use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Core\UserInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Http\Middleware\IdentityMiddleware;
use Webware\UserManager\Query\CheckUserActive;

#[CoversClass(IdentityMiddleware::class)]
#[CoversMethod(IdentityMiddleware::class, '__construct')]
#[CoversMethod(IdentityMiddleware::class, 'process')]
final class IdentityMiddlewareTest extends TestCase
{
    #[Test]
    public function clearsSessionAndCreatesGuestWhenStatusCheckFails(): void
    {
        $sessionData = ['id' => 5, 'email' => 'jane@example.com', 'roleId' => ['Member']];

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method('get')->with(UserInterface::class)->willReturn($sessionData);
        $session->expects($this->once())->method('clear');

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())
            ->method('handle')
            ->with(static::callback(static fn(CheckUserActive $query): bool => 5 === $query->id))
            ->willReturn(new QueryResult(new CheckUserActive(id: 5), MessageStatus::Success, false));

        $factoryData = null;
        $factory     = static function (array $data) use (&$factoryData): UserInterface {
            $factoryData = $data;

            return new User(roleId: $data['roleId'] ?? null);
        };

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new EmptyResponse());

        $middleware = new IdentityMiddleware($messageBus, $factory);

        $request = new ServerRequest()->withAttribute(SessionInterface::class, $session);
        $middleware->process($request, $handler);

        self::assertSame(['roleId' => UserInterface::GUEST_ROLE], $factoryData);
    }

    #[Test]
    public function createsGuestUserWhenNoSessionPresent(): void
    {
        $messageBus = $this->createStub(MessageBusInterface::class);

        $factoryData = null;
        $factory     = static function (array $data) use (&$factoryData): UserInterface {
            $factoryData = $data;

            return new User(roleId: $data['roleId'] ?? null);
        };

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

        $middleware = new IdentityMiddleware($messageBus, $factory);

        $response = $middleware->process(new ServerRequest(), $handler);

        self::assertInstanceOf(EmptyResponse::class, $response);
        self::assertSame(['roleId' => UserInterface::GUEST_ROLE], $factoryData);
        self::assertInstanceOf(User::class, $capturedRequest?->getAttribute(UserInterface::class));
    }

    #[Test]
    public function reconstructsUserFromValidSessionData(): void
    {
        $sessionData = ['id' => 5, 'email' => 'jane@example.com', 'roleId' => ['Member']];

        $session = $this->createStub(SessionInterface::class);
        $session->method('get')->willReturn($sessionData);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())
            ->method('handle')
            ->with(static::callback(static fn(CheckUserActive $query): bool => 5 === $query->id))
            ->willReturn(new QueryResult(new CheckUserActive(id: 5), MessageStatus::Success, true));

        $factoryData = null;
        $factory     = static function (array $data) use (&$factoryData): UserInterface {
            $factoryData = $data;

            return new User(
                id    : $data['id'] ?? null,
                email : $data['email'] ?? null,
                roleId: $data['roleId'] ?? null,
            );
        };

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

        $middleware = new IdentityMiddleware($messageBus, $factory);

        $request = new ServerRequest()->withAttribute(SessionInterface::class, $session);
        $middleware->process($request, $handler);

        self::assertSame($sessionData, $factoryData);
        self::assertInstanceOf(User::class, $capturedRequest?->getAttribute(UserInterface::class));
    }
}
