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
use Webware\UserManager\Http\Admin\RequestHandler\UpdateUserHandler;

#[CoversClass(UpdateUserHandler::class)]
#[CoversMethod(UpdateUserHandler::class, '__construct')]
#[CoversMethod(UpdateUserHandler::class, 'handle')]
final class UpdateUserHandlerTest extends TestCase
{
    #[Test]
    public function rendersUserListWithEmptyUsersByDefault(): void
    {
        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::list-users', ['users' => []])
            ->willReturn('<ul>');

        $handler = new UpdateUserHandler(template: $template);

        $response = $handler->handle(new ServerRequest());

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    #[Test]
    public function rendersUserListWithUpdatedUsers(): void
    {
        $updatedUsers = [new User(id: 1)];

        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::list-users', ['users' => $updatedUsers])
            ->willReturn('<ul>');

        $handler = new UpdateUserHandler(template: $template);

        $request = new ServerRequest()->withAttribute('updatedUsers', $updatedUsers);

        $response = $handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }
}
