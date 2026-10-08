<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Admin\RequestHandler;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Http\Admin\Middleware\UpdateUserModalMiddleware;
use Webware\UserManager\Http\Admin\RequestHandler\UpdateUserModalHandler;

#[CoversClass(UpdateUserModalHandler::class)]
#[CoversMethod(UpdateUserModalHandler::class, '__construct')]
#[CoversMethod(UpdateUserModalHandler::class, 'handle')]
final class UpdateUserModalHandlerTest extends TestCase
{
    #[Test]
    public function rendersModalFromTheAttachedViewModel(): void
    {
        $user = new User(
            id   : 5,
            email: 'jane@example.com',
        );

        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::update-user-modal', [
                'user'            => $user,
                'assignableRoles' => ['Member'],
                'layout'          => false,
                'body'            => false,
            ])
            ->willReturn('<form>');

        $handler = new UpdateUserModalHandler(template: $template);

        $request = new ServerRequest()->withAttribute(UpdateUserModalMiddleware::class, [
            'user'            => $user,
            'assignableRoles' => ['Member'],
        ]);

        $response = $handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('<form>', (string) $response->getBody());
    }

    #[Test]
    public function returnsNotFoundWhenNoViewModelIsAttached(): void
    {
        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->never())->method('render');

        $handler = new UpdateUserModalHandler(template: $template);

        $response = $handler->handle(new ServerRequest());

        self::assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenTheViewModelCarriesNoUser(): void
    {
        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->never())->method('render');

        $handler = new UpdateUserModalHandler(template: $template);

        $request = new ServerRequest()->withAttribute(UpdateUserModalMiddleware::class, [
            'user'            => null,
            'assignableRoles' => [],
        ]);

        $response = $handler->handle($request);

        self::assertSame(404, $response->getStatusCode());
    }
}
