<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Admin\RequestHandler;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Admin\RequestHandler\UpdateUserModalHandler;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Repository\UserRepositoryInterface;

#[CoversClass(UpdateUserModalHandler::class)]
#[CoversMethod(UpdateUserModalHandler::class, '__construct')]
#[CoversMethod(UpdateUserModalHandler::class, 'handle')]
final class UpdateUserModalHandlerTest extends TestCase
{
    #[Test]
    public function rendersModalForExistingUser(): void
    {
        $user = new User(
            id   : 5,
            email: 'jane@example.com',
        );

        $users = $this->createMock(UserRepositoryInterface::class);
        $users->expects($this->once())->method('findById')->with(5)->willReturn($user);

        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::update-user-modal', ['user' => $user, 'layout' => false, 'body' => false])
            ->willReturn('<form>');

        $handler = new UpdateUserModalHandler(
            template: $template,
            users   : $users,
        );

        $request = new ServerRequest()->withAttribute('id', '5');

        $response = $handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenUserMissing(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn(null);

        $handler = new UpdateUserModalHandler(
            template: $this->createStub(TemplateRendererInterface::class),
            users   : $users,
        );

        $request = new ServerRequest()->withAttribute('id', '99');

        $response = $handler->handle($request);

        self::assertSame(404, $response->getStatusCode());
    }
}
