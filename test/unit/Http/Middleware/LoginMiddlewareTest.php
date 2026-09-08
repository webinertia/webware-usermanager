<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Middleware;

use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Webware\Core\UserInterface;
use Webware\Message\SystemMessengerInterface;
use Webware\UserManager\Auth\AuthenticationResult;
use Webware\UserManager\Auth\AuthenticationStatus;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Http\Middleware\LoginMiddleware;
use Webware\UserManager\Repository\UserRepositoryInterface;

use function bin2hex;
use function random_bytes;

#[CoversClass(LoginMiddleware::class)]
#[CoversMethod(LoginMiddleware::class, '__construct')]
#[CoversMethod(LoginMiddleware::class, 'process')]
final class LoginMiddlewareTest extends TestCase
{
    #[Test]
    public function addsActivationHintWhenAccountNotActive(): void
    {
        $repository = $this->createStub(UserRepositoryInterface::class);
        $repository->method('authenticate')
            ->willReturn(new AuthenticationResult(AuthenticationStatus::NotActive));

        $messenger = $this->createMock(SystemMessengerInterface::class);
        $messenger->expects($this->once())->method('danger')->with('Invalid email or password.');
        $messenger->expects($this->once())->method('info')->with('Did you activate your account?');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn(new EmptyResponse());

        $middleware = $this->middleware(repository: $repository);

        $request = new ServerRequest()->withMethod('POST')
            ->withAttribute(SystemMessengerInterface::class, $messenger)
            ->withParsedBody(['email' => 'jane@example.com', 'password' => bin2hex(random_bytes(16))]);

        $response = $middleware->process($request, $handler);

        self::assertInstanceOf(EmptyResponse::class, $response);
    }

    #[Test]
    public function logsAndNotifiesOnInvalidCredentials(): void
    {
        $repository = $this->createStub(UserRepositoryInterface::class);
        $repository->method('authenticate')
            ->willReturn(new AuthenticationResult(AuthenticationStatus::InvalidCredentials));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Failed login attempt', ['email' => 'jane@example.com']);

        $messenger = $this->createMock(SystemMessengerInterface::class);
        $messenger->expects($this->once())->method('danger')->with('Invalid email or password.');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn(new EmptyResponse());

        $middleware = $this->middleware(
            repository: $repository,
            logger    : $logger,
        );

        $request = new ServerRequest()->withMethod('POST')
            ->withAttribute(SystemMessengerInterface::class, $messenger)
            ->withParsedBody(['email' => 'jane@example.com', 'password' => bin2hex(random_bytes(16))]);

        $response = $middleware->process($request, $handler);

        self::assertInstanceOf(EmptyResponse::class, $response);
    }

    #[Test]
    public function passesThroughNonPostRequests(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn(new EmptyResponse());

        $middleware = $this->middleware();

        $response = $middleware->process(new ServerRequest(), $handler);

        self::assertInstanceOf(EmptyResponse::class, $response);
    }

    #[Test]
    public function passesThroughWhenCredentialsMissing(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn(new EmptyResponse());

        $middleware = $this->middleware();

        $request = new ServerRequest()->withMethod('POST')
            ->withParsedBody(['email' => 'jane@example.com']);

        $response = $middleware->process($request, $handler);

        self::assertInstanceOf(EmptyResponse::class, $response);
    }

    #[Test]
    public function storesSessionAndRedirectsOnSuccess(): void
    {
        $password = bin2hex(random_bytes(16));
        $user     = new User(
            id    : 1,
            email : 'jane@example.com',
            active: true,
        );

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('authenticate')
            ->with('jane@example.com', $password)
            ->willReturn(new AuthenticationResult(AuthenticationStatus::Success, $user));

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method('set')->with(UserInterface::class, $user->toArray());
        $session->expects($this->once())->method('regenerate')->willReturnSelf();

        $middleware = $this->middleware(repository: $repository);

        $request = new ServerRequest()->withMethod('POST')
            ->withAttribute(SessionInterface::class, $session)
            ->withParsedBody(['email' => 'jane@example.com', 'password' => $password]);

        $response = $middleware->process($request, $this->createStub(RequestHandlerInterface::class));

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/dashboard', $response->getHeaderLine('Location'));
    }

    private function middleware(
        ?UserRepositoryInterface $repository = null,
        ?LoggerInterface $logger = null,
    ): LoginMiddleware {
        $repository ??= $this->createStub(UserRepositoryInterface::class);
        $logger     ??= $this->createStub(LoggerInterface::class);

        return new LoginMiddleware(
            repository : $repository,
            logger     : $logger,
            redirectUrl: '/dashboard',
        );
    }
}
