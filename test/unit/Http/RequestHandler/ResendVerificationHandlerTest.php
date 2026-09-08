<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\RequestHandler;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Http\RequestHandler\ResendVerificationHandler;

#[CoversClass(ResendVerificationHandler::class)]
#[CoversMethod(ResendVerificationHandler::class, '__construct')]
#[CoversMethod(ResendVerificationHandler::class, 'handle')]
final class ResendVerificationHandlerTest extends TestCase
{
    #[Test]
    public function redirectsToLoginWhenRedirectViewModel(): void
    {
        $handler = new ResendVerificationHandler(
            template: $this->createStub(TemplateRendererInterface::class),
            loginUrl: '/user.manager/login',
        );

        $request = new ServerRequest()->withAttribute(
            ResendVerificationHandler::class,
            ['redirect' => true],
        );

        $response = $handler->handle($request);

        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertSame('/user.manager/login', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function rendersFormWhenNoViewModel(): void
    {
        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::resend-verification')
            ->willReturn('<form>');

        $handler = new ResendVerificationHandler(
            template: $template,
            loginUrl: '/user.manager/login',
        );

        $response = $handler->handle(new ServerRequest());

        static::assertInstanceOf(HtmlResponse::class, $response);
    }

    #[Test]
    public function rendersViewModelWhenPresent(): void
    {
        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::resend-verification', ['sent' => true])
            ->willReturn('<p>sent</p>');

        $handler = new ResendVerificationHandler(
            template: $template,
            loginUrl: '/user.manager/login',
        );

        $request = new ServerRequest()->withAttribute(
            ResendVerificationHandler::class,
            ['sent' => true],
        );

        $response = $handler->handle($request);

        static::assertInstanceOf(HtmlResponse::class, $response);
    }
}
