<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\RequestHandler;

use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\RequestHandler\LogoutHandler;

#[CoversClass(LogoutHandler::class)]
#[CoversMethod(LogoutHandler::class, '__construct')]
#[CoversMethod(LogoutHandler::class, 'handle')]
final class LogoutHandlerTest extends TestCase
{
    #[Test]
    public function clearsSessionBeforeRedirecting(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method('clear');

        $handler = new LogoutHandler(loginUrl: '/user.manager/login');

        $request = new ServerRequest()->withAttribute(SessionInterface::class, $session);

        $response = $handler->handle($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/user.manager/login', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function redirectsToLoginWhenNoSessionPresent(): void
    {
        $handler = new LogoutHandler(loginUrl: '/user.manager/login');

        $response = $handler->handle(new ServerRequest());

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/user.manager/login', $response->getHeaderLine('Location'));
    }
}
