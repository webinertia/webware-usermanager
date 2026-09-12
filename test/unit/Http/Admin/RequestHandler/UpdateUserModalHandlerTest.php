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
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Http\Admin\RequestHandler\UpdateUserModalHandler;
use Webware\UserManager\Query\FetchUserByIdQuery;

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

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())
            ->method('handle')
            ->with(
                static::callback(
                    static fn(FetchUserByIdQuery $query): bool => 5 === $query->id,
                ),
            )
            ->willReturn(new QueryResult(new FetchUserByIdQuery(id: 5), MessageStatus::Success, $user));

        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::update-user-modal', ['user' => $user, 'layout' => false, 'body' => false])
            ->willReturn('<form>');

        $handler = new UpdateUserModalHandler(
            template  : $template,
            messageBus: $messageBus,
        );

        $request = new ServerRequest()->withAttribute('id', '5');

        $response = $handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenIdIsInvalid(): void
    {
        $handler = new UpdateUserModalHandler(
            template  : $this->createStub(TemplateRendererInterface::class),
            messageBus: $this->createStub(MessageBusInterface::class),
        );

        $request = new ServerRequest()->withAttribute('id', 'not-a-number');

        $response = $handler->handle($request);

        self::assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenUserMissing(): void
    {
        $messageBus = $this->createStub(MessageBusInterface::class);
        $messageBus->method('handle')
            ->willReturn(new QueryResult(new FetchUserByIdQuery(id: 99), MessageStatus::Failure, null));

        $handler = new UpdateUserModalHandler(
            template  : $this->createStub(TemplateRendererInterface::class),
            messageBus: $messageBus,
        );

        $request = new ServerRequest()->withAttribute('id', '99');

        $response = $handler->handle($request);

        self::assertSame(404, $response->getStatusCode());
    }
}
