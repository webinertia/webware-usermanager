<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\RequestHandler;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\MailerInterface;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Repository\UserRepositoryInterface;
use Webware\UserManager\RequestHandler\ResendVerificationHandler;
use Webware\UserManager\View\Helper\UserUrl;

use function is_string;

#[CoversClass(ResendVerificationHandler::class)]
#[CoversMethod(ResendVerificationHandler::class, '__construct')]
#[CoversMethod(ResendVerificationHandler::class, 'handle')]
final class ResendVerificationHandlerTest extends TestCase
{
    #[Test]
    public function redirectsActiveUserToLogin(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByEmail')
            ->willReturn(new User(
                id    : 1,
                active: true,
            ));

        $handler = $this->handler(users: $users);

        $request = new ServerRequest()->withMethod('POST')
            ->withParsedBody(['email' => 'jane@example.com']);

        $response = $handler->handle($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/user.manager/login', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function rendersErrorOnEmptyEmail(): void
    {
        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::resend-verification', ['error' => 'Please enter a valid email address.'])
            ->willReturn('<form>');

        $handler = $this->handler(template: $template);

        $request = new ServerRequest()->withMethod('POST')
            ->withParsedBody(['email' => '']);

        $response = $handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    #[Test]
    public function rendersFormOnGet(): void
    {
        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::resend-verification')
            ->willReturn('<form>');

        $handler = $this->handler(template: $template);

        $response = $handler->handle(new ServerRequest());

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    #[Test]
    public function silentlySkipsUnknownEmail(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn(null);

        $mailer = $this->createStub(MailerInterface::class);
        $mailer->method('getAdapter')->willReturn(null);

        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::resend-verification', ['sent' => true])
            ->willReturn('<p>sent</p>');

        $handler = $this->handler(
            template: $template,
            users   : $users,
            mailer  : $mailer,
        );

        $request = new ServerRequest()->withMethod('POST')
            ->withParsedBody(['email' => 'ghost@example.com']);

        $response = $handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    #[Test]
    public function updatesTokenAndSendsEmailForInactiveUser(): void
    {
        $user = new User(
            id       : 7,
            firstName: 'Jane',
            lastName : 'Doe',
            email    : 'jane@example.com',
            active   : false,
        );

        $users = $this->createMock(UserRepositoryInterface::class);
        $users->expects($this->once())->method('findByEmail')->with('jane@example.com')->willReturn($user);
        $users->expects($this->once())
            ->method('update')
            ->with(
                7,
                $this->callback(
                    static fn(array $data): bool => (
                        is_string($data['verificationToken'] ?? null)
                        && is_string($data['tokenCreatedAt'] ?? null)
                    ),
                ),
            )
            ->willReturn(1);

        $adapter = $this->createStub(AdapterInterface::class);
        $adapter->method('from')->willReturnSelf();
        $adapter->method('to')->willReturnSelf();
        $adapter->method('subject')->willReturnSelf();
        $adapter->method('isHtml')->willReturnSelf();
        $adapter->method('body')->willReturnSelf();
        $adapter->method('altBody')->willReturnSelf();

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('getAdapter')->willReturn($adapter);
        $mailer->expects($this->once())->method('send')->willReturn(true);

        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::resend-verification', ['sent' => true])
            ->willReturn('<p>sent</p>');

        $handler = $this->handler(
            template: $template,
            users   : $users,
            mailer  : $mailer,
        );

        $request = new ServerRequest()->withMethod('POST')
            ->withParsedBody(['email' => 'jane@example.com']);

        $response = $handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    private function handler(
        ?TemplateRendererInterface $template = null,
        ?UserRepositoryInterface $users = null,
        ?MailerInterface $mailer = null,
    ): ResendVerificationHandler {
        $template ??= $this->createStub(TemplateRendererInterface::class);
        $users    ??= $this->createStub(UserRepositoryInterface::class);
        $mailer   ??= $this->createStub(MailerInterface::class);

        $urlHelper = $this->createStub(UrlHelper::class);
        $urlHelper->method('__invoke')->willReturn('/user/verify-email');

        return new ResendVerificationHandler(
            template           : $template,
            users              : $users,
            mailer             : $mailer,
            fromEmail          : 'noreply@example.com',
            fromName           : 'Webware',
            baseUrl            : 'https://example.com',
            verificationSubject: 'Verify your email',
            loginUrl           : '/user.manager/login',
            userUrl            : new UserUrl($urlHelper, 'user.manager.'),
        );
    }
}
