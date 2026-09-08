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
use Webware\MessageBus\Command\CommandInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Admin\RequestHandler\ToggleUserActiveHandler;
use Webware\UserManager\Entity\User;

#[CoversClass(ToggleUserActiveHandler::class)]
#[CoversMethod(ToggleUserActiveHandler::class, '__construct')]
#[CoversMethod(ToggleUserActiveHandler::class, 'handle')]
final class ToggleUserActiveHandlerTest extends TestCase
{
    #[Test]
    public function rendersUserRowOnSuccess(): void
    {
        $user = new User(
            id   : 1,
            email: 'jane@example.com',
        );

        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::partials/user-row', ['user' => $user, 'layout' => false, 'body' => false])
            ->willReturn('<tr>');

        $handler = new ToggleUserActiveHandler(template: $template);

        $result  = new CommandResult($this->createStub(CommandInterface::class), MessageStatus::Success, $user);
        $request = new ServerRequest()->withAttribute(CommandResult::class, $result);

        $response = $handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('<tr>', (string) $response->getBody());
    }

    #[Test]
    public function returnsNotFoundWhenResultIsNotAUser(): void
    {
        $handler = new ToggleUserActiveHandler(template: $this->createStub(TemplateRendererInterface::class));

        $result  = new CommandResult($this->createStub(CommandInterface::class), MessageStatus::Success, 'not-a-user');
        $request = new ServerRequest()->withAttribute(CommandResult::class, $result);

        $response = $handler->handle($request);

        self::assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function returnsUnprocessableOnFailure(): void
    {
        $handler = new ToggleUserActiveHandler(template: $this->createStub(TemplateRendererInterface::class));

        $result  = new CommandResult($this->createStub(CommandInterface::class), MessageStatus::Failure, null);
        $request = new ServerRequest()->withAttribute(CommandResult::class, $result);

        $response = $handler->handle($request);

        self::assertSame(422, $response->getStatusCode());
    }

    #[Test]
    public function returnsUnprocessableWhenNoResultPresent(): void
    {
        $handler = new ToggleUserActiveHandler(template: $this->createStub(TemplateRendererInterface::class));

        $response = $handler->handle(new ServerRequest());

        self::assertInstanceOf(HtmlResponse::class, $response);
        self::assertSame(422, $response->getStatusCode());
    }
}
