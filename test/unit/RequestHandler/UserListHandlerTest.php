<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\RequestHandler;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PhpDb\ResultSet\RowPrototypeResultSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Htmx\Response\Header;
use Webware\MessageBus\Command\CommandInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Repository\UserRepositoryInterface;
use Webware\UserManager\RequestHandler\UserListHandler;

#[CoversClass(UserListHandler::class)]
#[CoversMethod(UserListHandler::class, '__construct')]
#[CoversMethod(UserListHandler::class, 'handle')]
final class UserListHandlerTest extends TestCase
{
    #[Test]
    public function addsCloseModalTriggerOnSuccess(): void
    {
        $resultSet = new RowPrototypeResultSet(new User());

        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findAll')->willReturn($resultSet);

        $template = $this->createStub(TemplateRendererInterface::class);
        $template->method('render')->willReturn('<ul>');

        $handler = new UserListHandler(
            template: $template,
            users   : $users,
        );

        $result  = new CommandResult($this->createStub(CommandInterface::class), MessageStatus::Success, null);
        $request = new ServerRequest()->withAttribute(CommandResult::class, $result);

        $response = $handler->handle($request);

        self::assertTrue($response->hasHeader(Header::Trigger->value));
        self::assertSame('{"closeModal":null}', $response->getHeaderLine(Header::Trigger->value));
    }

    #[Test]
    public function rendersUserListWithoutTrigger(): void
    {
        $resultSet = new RowPrototypeResultSet(new User());

        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findAll')->willReturn($resultSet);

        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::list-users', ['users' => $resultSet])
            ->willReturn('<ul>');

        $handler = new UserListHandler(
            template: $template,
            users   : $users,
        );

        $response = $handler->handle(new ServerRequest());

        self::assertInstanceOf(HtmlResponse::class, $response);
        self::assertFalse($response->hasHeader(Header::Trigger->value));
    }
}
