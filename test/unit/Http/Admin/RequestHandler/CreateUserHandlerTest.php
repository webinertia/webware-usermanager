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
use Webware\UserManager\Http\Admin\RequestHandler\CreateUserHandler;

#[CoversClass(CreateUserHandler::class)]
#[CoversMethod(CreateUserHandler::class, '__construct')]
#[CoversMethod(CreateUserHandler::class, 'handle')]
final class CreateUserHandlerTest extends TestCase
{
    #[Test]
    public function rendersCreateUserForm(): void
    {
        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::create-user')
            ->willReturn('<form>');

        $handler = new CreateUserHandler(template: $template);

        $response = $handler->handle(new ServerRequest());

        self::assertInstanceOf(HtmlResponse::class, $response);
        self::assertSame('<form>', (string) $response->getBody());
    }
}
