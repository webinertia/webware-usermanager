<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\RequestHandler;

use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Core\Role;
use Webware\Core\UserInterface;
use Webware\Htmx\Attribute;
use Webware\Htmx\Response\Header;
use Webware\Message\SystemMessengerInterface;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Http\RequestHandler\LoginHandler;

#[CoversClass(LoginHandler::class)]
#[CoversMethod(LoginHandler::class, '__construct')]
#[CoversMethod(LoginHandler::class, 'handle')]
final class LoginHandlerTest extends TestCase
{
    #[Test]
    public function passesTheConfiguredFieldNamesToTheTemplate(): void
    {
        $template = $this->createMock(TemplateRendererInterface::class);
        $template
            ->expects($this->once())
            ->method('render')
            ->with('user::login', [
                'credentialField' => 'user_name',
                'passwordField'   => 'pass',
                'flashMessages'   => [],
            ])
            ->willReturn('<form>');

        $handler = new LoginHandler(
            template       : $template,
            credentialField: 'user_name',
            passwordField  : 'pass',
        );

        $handler->handle(new ServerRequest()->withAttribute(
            UserInterface::class,
            new User(roleId: Role::Guest->value),
        ));
    }

    #[Test]
    public function redirectsAuthenticatedUser(): void
    {
        $handler = new LoginHandler(
            template       : $this->createStub(TemplateRendererInterface::class),
            credentialField: 'email',
            passwordField  : 'password',
        );

        $request = new ServerRequest()->withAttribute(
            UserInterface::class,
            new User(
                email : 'jane@example.com',
                roleId: 'Member',
            ),
        );

        $response = $handler->handle($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function rendersLoginFormForGuestWithFlashMessages(): void
    {
        $messenger = $this->createStub(SystemMessengerInterface::class);
        $messenger->method('getMessages')->willReturn(['danger' => ['Invalid email or password.']]);

        $template = $this->createMock(TemplateRendererInterface::class);
        $template
            ->expects($this->once())
            ->method('render')
            ->with('user::login', [
                'credentialField' => 'email',
                'passwordField'   => 'password',
                'flashMessages'   => ['danger' => ['Invalid email or password.']],
            ])
            ->willReturn('<form>');

        $handler = new LoginHandler(
            template       : $template,
            credentialField: 'email',
            passwordField  : 'password',
        );

        $request = new ServerRequest()
            ->withAttribute(
                UserInterface::class,
                new User(roleId: Role::Guest->value),
            )
            ->withAttribute(SystemMessengerInterface::class, $messenger);

        $response = $handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
        self::assertSame('<form>', (string) $response->getBody());
    }

    #[Test]
    public function returnsHtmxRedirectForBoostedAuthenticatedRequest(): void
    {
        $handler = new LoginHandler(
            template       : $this->createStub(TemplateRendererInterface::class),
            credentialField: 'email',
            passwordField  : 'password',
        );

        $request = new ServerRequest()
            ->withAttribute(UserInterface::class, new User(
                email : 'jane@example.com',
                roleId: 'Member',
            ))
            ->withAttribute(Attribute::Request->value, true);

        $response = $handler->handle($request);

        self::assertInstanceOf(EmptyResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('/', $response->getHeaderLine(Header::Redirect->value));
    }
}
