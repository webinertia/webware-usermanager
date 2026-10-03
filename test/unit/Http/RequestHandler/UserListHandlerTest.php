<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\RequestHandler;

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
use Webware\UserManager\Http\Middleware\UserListMiddleware;
use Webware\UserManager\Http\RequestHandler\UserListHandler;

#[CoversClass(UserListHandler::class)]
#[CoversMethod(UserListHandler::class, '__construct')]
#[CoversMethod(UserListHandler::class, 'handle')]
final class UserListHandlerTest extends TestCase
{
    #[Test]
    public function addsCloseModalTriggerOnSuccess(): void
    {
        $template = $this->createStub(TemplateRendererInterface::class);
        $template->method('render')->willReturn('<ul>');

        $handler = new UserListHandler(template: $template);

        $result  = new CommandResult($this->createStub(CommandInterface::class), MessageStatus::Success, null);
        $request = new ServerRequest()->withAttribute(UserListMiddleware::class, ['users' => []])
            ->withAttribute(CommandResult::class, $result);

        $response = $handler->handle($request);

        self::assertTrue($response->hasHeader(Header::Trigger->value));
        self::assertSame('{"closeModal":null}', $response->getHeaderLine(Header::Trigger->value));
    }

    #[Test]
    public function doesNotAddTheTriggerWhenTheCommandFailed(): void
    {
        $template = $this->createStub(TemplateRendererInterface::class);
        $template->method('render')->willReturn('<ul>');

        $handler = new UserListHandler(template: $template);

        $result  = new CommandResult($this->createStub(CommandInterface::class), MessageStatus::Failure, null);
        $request = new ServerRequest()->withAttribute(CommandResult::class, $result);

        $response = $handler->handle($request);

        self::assertFalse($response->hasHeader(Header::Trigger->value));
    }

    #[Test]
    public function rendersAnEmptyListWhenTheMiddlewareDidNotRun(): void
    {
        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::list-users', ['users' => []])
            ->willReturn('<ul>');

        $handler = new UserListHandler(template: $template);

        $response = $handler->handle(new ServerRequest());

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    #[Test]
    public function rendersTheViewModelAttachedByTheMiddleware(): void
    {
        $users = [new User(id: 1)];

        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::list-users', ['users' => $users])
            ->willReturn('<ul>');

        $handler = new UserListHandler(template: $template);

        $request = new ServerRequest()->withAttribute(UserListMiddleware::class, ['users' => $users]);

        $response = $handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
        self::assertFalse($response->hasHeader(Header::Trigger->value));
    }
}
