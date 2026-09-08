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
use Webware\UserManager\Http\RequestHandler\VerifyEmailHandler;

#[CoversClass(VerifyEmailHandler::class)]
#[CoversMethod(VerifyEmailHandler::class, '__construct')]
#[CoversMethod(VerifyEmailHandler::class, 'handle')]
final class VerifyEmailHandlerTest extends TestCase
{
    #[Test]
    public function redirectsToLoginWhenNoTemplateParams(): void
    {
        $handler = new VerifyEmailHandler(
            template: $this->createStub(TemplateRendererInterface::class),
            loginUrl: '/user.manager/login',
        );

        $response = $handler->handle(new ServerRequest());

        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertSame('/user.manager/login', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function rendersErrorWhenTemplateParamsProvided(): void
    {
        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::verify-email', ['error' => 'Invalid verification link.'])
            ->willReturn('<p>error</p>');

        $handler = new VerifyEmailHandler(
            template: $template,
            loginUrl: '/user.manager/login',
        );

        $request = new ServerRequest()->withAttribute(
            VerifyEmailHandler::class,
            ['error' => 'Invalid verification link.'],
        );

        $response = $handler->handle($request);

        static::assertInstanceOf(HtmlResponse::class, $response);
    }
}
