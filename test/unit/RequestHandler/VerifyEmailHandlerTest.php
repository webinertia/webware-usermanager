<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\RequestHandler;

use DateTimeImmutable;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Message\SystemMessengerInterface;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Repository\UserRepositoryInterface;
use Webware\UserManager\RequestHandler\VerifyEmailHandler;

use function bin2hex;
use function random_bytes;

#[CoversClass(VerifyEmailHandler::class)]
#[CoversMethod(VerifyEmailHandler::class, '__construct')]
#[CoversMethod(VerifyEmailHandler::class, 'handle')]
final class VerifyEmailHandlerTest extends TestCase
{
    #[Test]
    public function activatesUserAndRedirectsOnValidToken(): void
    {
        $token = bin2hex(random_bytes(16));

        $user = new User(id: 3);

        $users = $this->createMock(UserRepositoryInterface::class);
        $users->expects($this->once())->method('findByVerificationToken')->willReturn($user);
        $users->expects($this->once())
            ->method('update')
            ->with(3, ['active' => 1, 'verificationToken' => null, 'tokenCreatedAt' => null])
            ->willReturn(1);

        $messenger = $this->createMock(SystemMessengerInterface::class);
        $messenger->expects($this->once())
            ->method('success')
            ->with('Email verified! You may now sign in.', 1, false);

        $handler = $this->handler(users: $users);

        $request = new ServerRequest()->withAttribute('token', $token)
            ->withAttribute(SystemMessengerInterface::class, $messenger);

        $response = $handler->handle($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/user.manager/login', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function rendersErrorOnMissingToken(): void
    {
        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::verify-email', ['error' => 'Invalid verification link.'])
            ->willReturn('<p>error</p>');

        $handler = $this->handler(template: $template);

        $response = $handler->handle(new ServerRequest());

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    #[Test]
    public function rendersErrorOnUnknownToken(): void
    {
        $token = bin2hex(random_bytes(16));

        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByVerificationToken')->willReturn(null);

        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::verify-email', ['error' => 'Invalid or already used verification link.'])
            ->willReturn('<p>error</p>');

        $handler = $this->handler(
            template: $template,
            users   : $users,
        );

        $request = new ServerRequest()->withAttribute('token', $token);

        $response = $handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    #[Test]
    public function rendersErrorWhenTokenExpired(): void
    {
        $token = bin2hex(random_bytes(16));

        $user = new User(
            id            : 1,
            tokenCreatedAt: new DateTimeImmutable('-2 hours'),
        );

        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByVerificationToken')->willReturn($user);

        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::verify-email', ['error' => 'Your verification link has expired.', 'expired' => true])
            ->willReturn('<p>expired</p>');

        $handler = $this->handler(
            template: $template,
            users   : $users,
        );

        $request = new ServerRequest()->withAttribute('token', $token);

        $response = $handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    private function handler(
        ?TemplateRendererInterface $template = null,
        ?UserRepositoryInterface $users = null,
    ): VerifyEmailHandler {
        $template ??= $this->createStub(TemplateRendererInterface::class);
        $users    ??= $this->createStub(UserRepositoryInterface::class);

        return new VerifyEmailHandler(
            template: $template,
            users   : $users,
            tokenTtl: 3600,
            loginUrl: '/user.manager/login',
        );
    }
}
