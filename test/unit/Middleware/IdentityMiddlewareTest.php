<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Middleware;

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
use Webware\UserManager\Entity\User;
use Webware\UserManager\Middleware\IdentityMiddleware;
use Webware\UserManager\Repository\UserRepositoryInterface;

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

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())->method('checkStatus')->with(5)->willReturn(false);

        $factoryData = null;
        $factory     = static function (array $data) use (&$factoryData): UserInterface {
            $factoryData = $data;

            return new User(roleId: $data['roleId'] ?? null);
        };

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new EmptyResponse());

        $middleware = new IdentityMiddleware($repository, $factory);

        $request = new ServerRequest()->withAttribute(SessionInterface::class, $session);
        $middleware->process($request, $handler);

        self::assertSame(['roleId' => UserInterface::GUEST_ROLE], $factoryData);
    }

    #[Test]
    public function createsGuestUserWhenNoSessionPresent(): void
    {
        $repository = $this->createStub(UserRepositoryInterface::class);

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

        $middleware = new IdentityMiddleware($repository, $factory);

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

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())->method('checkStatus')->with(5)->willReturn(true);

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

        $middleware = new IdentityMiddleware($repository, $factory);

        $request = new ServerRequest()->withAttribute(SessionInterface::class, $session);
        $middleware->process($request, $handler);

        self::assertSame($sessionData, $factoryData);
        self::assertInstanceOf(User::class, $capturedRequest?->getAttribute(UserInterface::class));
    }
}
