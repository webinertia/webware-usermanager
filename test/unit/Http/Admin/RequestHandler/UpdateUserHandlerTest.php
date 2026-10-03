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
use Webware\Htmx\Response\Header;
use Webware\MessageBus\Command\CommandInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Http\Admin\RequestHandler\UpdateUserHandler;
use Webware\UserManager\Http\Middleware\UserListMiddleware;
use Webware\UserManager\Http\RequestHandler\UserListHandler;

#[CoversClass(UpdateUserHandler::class)]
#[CoversMethod(UpdateUserHandler::class, '__construct')]
#[CoversMethod(UpdateUserHandler::class, 'handle')]
final class UpdateUserHandlerTest extends TestCase
{
    private const string LIST_URL = '/admin/user';

    #[Test]
    public function closesTheModalWhenTheCommandSucceeded(): void
    {
        $handler = $this->handler();

        $result = new CommandResult($this->createStub(CommandInterface::class), MessageStatus::Success, null);

        $response = $handler->handle(new ServerRequest()->withAttribute(CommandResult::class, $result));

        self::assertSame('{"closeModal":null}', $response->getHeaderLine(Header::Trigger->value));
        self::assertSame(self::LIST_URL, $response->getHeaderLine(Header::PushUrl->value));
    }

    #[Test]
    public function doesNotCloseTheModalWhenTheCommandFailed(): void
    {
        $handler = $this->handler();

        $result = new CommandResult($this->createStub(CommandInterface::class), MessageStatus::Failure, null);

        $response = $handler->handle(new ServerRequest()->withAttribute(CommandResult::class, $result));

        self::assertFalse($response->hasHeader(Header::Trigger->value));
    }

    #[Test]
    public function rendersTheListAndPushesTheListUrl(): void
    {
        $users = [new User(id: 1)];

        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::list-users', ['users' => $users])
            ->willReturn('<ul>');

        $handler = new UpdateUserHandler(
            listHandler: new UserListHandler(template: $template),
            listUrl    : self::LIST_URL,
        );

        $request = new ServerRequest()->withAttribute(UserListMiddleware::class, ['users' => $users]);

        $response = $handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
        self::assertSame(self::LIST_URL, $response->getHeaderLine(Header::PushUrl->value));
        self::assertFalse($response->hasHeader(Header::Trigger->value));
    }

    private function handler(): UpdateUserHandler
    {
        $template = $this->createStub(TemplateRendererInterface::class);
        $template->method('render')->willReturn('<ul>');

        return new UpdateUserHandler(
            listHandler: new UserListHandler(template: $template),
            listUrl    : self::LIST_URL,
        );
    }
}
