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
    public function redirectsAuthenticatedUser(): void
    {
        $handler = new LoginHandler($this->createStub(TemplateRendererInterface::class));

        $request = new ServerRequest()->withAttribute(
            UserInterface::class,
            new User(email: 'jane@example.com'),
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
        $template->expects($this->once())
            ->method('render')
            ->with('user::login', ['flashMessages' => ['danger' => ['Invalid email or password.']]])
            ->willReturn('<form>');

        $handler = new LoginHandler($template);

        $request = new ServerRequest()->withAttribute(
            UserInterface::class,
            new User(roleId: UserInterface::GUEST_ROLE),
        )
            ->withAttribute(SystemMessengerInterface::class, $messenger);

        $response = $handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
        self::assertSame('<form>', (string) $response->getBody());
    }

    #[Test]
    public function returnsHtmxRedirectForBoostedAuthenticatedRequest(): void
    {
        $handler = new LoginHandler($this->createStub(TemplateRendererInterface::class));

        $request = new ServerRequest()->withAttribute(UserInterface::class, new User(email: 'jane@example.com'))
            ->withAttribute(Attribute::Request->value, true);

        $response = $handler->handle($request);

        self::assertInstanceOf(EmptyResponse::class, $response);
        self::assertSame('/', $response->getHeaderLine(Header::Redirect->value));
    }
}
