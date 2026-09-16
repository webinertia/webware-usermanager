<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Middleware;

use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Session\Exception\SessionNotInitializedException;
use Mezzio\Session\Session;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Session\SessionPersistenceInterface;
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
use Webware\UserManager\Query\CheckUserActiveQuery;

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
            ->with(static::callback(static fn(CheckUserActiveQuery $query): bool => 5 === $query->id))
            ->willReturn(new QueryResult(new CheckUserActiveQuery(id: 5), MessageStatus::Success, false));

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
    public function createsGuestUserWhenSessionCarriesNoIdentity(): void
    {
        $session = $this->createStub(SessionInterface::class);

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

        $response = $middleware->process(
            new ServerRequest()->withAttribute(SessionInterface::class, $session),
            $handler,
        );

        self::assertInstanceOf(EmptyResponse::class, $response);
        self::assertSame(['roleId' => UserInterface::GUEST_ROLE], $factoryData);
        self::assertInstanceOf(User::class, $capturedRequest?->getAttribute(UserInterface::class));
    }

    #[Test]
    public function fallsBackToZeroIdWhenSessionLacksId(): void
    {
        $sessionData = ['email' => 'jane@example.com', 'roleId' => ['Member']];

        $session = $this->createStub(SessionInterface::class);
        $session->method('get')->willReturn($sessionData);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())
            ->method('handle')
            ->with(static::callback(static fn(CheckUserActiveQuery $query): bool => 0 === $query->id))
            ->willReturn(new QueryResult(new CheckUserActiveQuery(id: 0), MessageStatus::Success, true));

        $factory = static fn(array $data): UserInterface => new User(roleId: $data['roleId'] ?? null);

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
        $middleware->process(
            new ServerRequest()->withAttribute(SessionInterface::class, $session),
            $handler,
        );

        static::assertInstanceOf(User::class, $capturedRequest?->getAttribute(UserInterface::class));
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
            ->with(static::callback(static fn(CheckUserActiveQuery $query): bool => 5 === $query->id))
            ->willReturn(new QueryResult(new CheckUserActiveQuery(id: 5), MessageStatus::Success, true));

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

    /**
     * Proves the contract behind RetrieveSession::fromRequest(): as long as
     * SessionMiddleware is piped, a session is attached to the request even on a
     * first page load by an anonymous visitor with no session cookie, so
     * IdentityMiddleware resolves a guest rather than failing.
     */
    #[Test]
    public function resolvesGuestIdentityWhenSessionMiddlewareIsPiped(): void
    {
        $persistence = $this->createStub(SessionPersistenceInterface::class);
        $persistence->method('initializeSessionFromRequest')->willReturn(new Session([]));
        $persistence->method('persistSession')
            ->willReturnCallback(
                static fn(SessionInterface $session, ResponseInterface $response): ResponseInterface => $response,
            );

        $factoryData = null;
        $factory     = static function (array $data) use (&$factoryData): UserInterface {
            $factoryData = $data;

            return new User(roleId: $data['roleId'] ?? null);
        };

        $identity = new IdentityMiddleware($this->createStub(MessageBusInterface::class), $factory);

        $terminal = $this->createMock(RequestHandlerInterface::class);
        $terminal->expects($this->once())->method('handle')->willReturn(new EmptyResponse());

        // Stands in for the remainder of the pipeline: IdentityMiddleware runs
        // behind SessionMiddleware, exactly as the global pipeline pipes it.
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(
                static fn(ServerRequestInterface $request): ResponseInterface => $identity->process(
                    $request,
                    $terminal,
                ),
            );

        $response = new SessionMiddleware($persistence)->process(new ServerRequest(), $handler);

        self::assertInstanceOf(EmptyResponse::class, $response);
        self::assertSame(['roleId' => UserInterface::GUEST_ROLE], $factoryData);
    }

    #[Test]
    public function throwsWhenSessionMiddlewareIsNotPiped(): void
    {
        $factory = static fn(array $data): UserInterface => new User(roleId: $data['roleId'] ?? null);

        $middleware = new IdentityMiddleware($this->createStub(MessageBusInterface::class), $factory);

        $this->expectException(SessionNotInitializedException::class);

        $middleware->process(new ServerRequest(), $this->createStub(RequestHandlerInterface::class));
    }
}
