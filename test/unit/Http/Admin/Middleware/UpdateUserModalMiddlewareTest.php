<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Admin\Middleware;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Core\UserInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Http\Admin\AssignableRolesProvider;
use Webware\UserManager\Http\Admin\Middleware\UpdateUserModalMiddleware;
use Webware\UserManager\Query\FetchAssignableRolesQuery;
use Webware\UserManager\Query\FetchUserByIdQuery;

use function assert;

#[CoversClass(UpdateUserModalMiddleware::class)]
#[CoversMethod(UpdateUserModalMiddleware::class, '__construct')]
#[CoversMethod(UpdateUserModalMiddleware::class, 'process')]
final class UpdateUserModalMiddlewareTest extends TestCase
{
    #[Test]
    public function attachesNoUserWhenTheIdIsInvalid(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->never())->method('handle');

        $attached = null;

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(
                static function (ServerRequestInterface $request) use (&$attached): ResponseInterface {
                    $attached = $request->getAttribute(UpdateUserModalMiddleware::class);

                    return new HtmlResponse('', 404);
                },
            );

        $middleware = new UpdateUserModalMiddleware(
            messageBus     : $messageBus,
            assignableRoles: new AssignableRolesProvider($messageBus),
        );

        $middleware->process(new ServerRequest()->withAttribute('id', 'not-a-number'), $handler);

        self::assertSame(['user' => null, 'assignableRoles' => []], $attached);
    }

    #[Test]
    public function attachesNoUserWhenTheQueryFails(): void
    {
        $messageBus = $this->createStub(MessageBusInterface::class);
        $messageBus->method('handle')
            ->willReturn(new QueryResult(new FetchUserByIdQuery(id: 99), MessageStatus::Failure, null));

        $attached = null;

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(
                static function (ServerRequestInterface $request) use (&$attached): ResponseInterface {
                    $attached = $request->getAttribute(UpdateUserModalMiddleware::class);

                    return new HtmlResponse('', 404);
                },
            );

        $middleware = new UpdateUserModalMiddleware(
            messageBus     : $messageBus,
            assignableRoles: new AssignableRolesProvider($messageBus),
        );

        $middleware->process(new ServerRequest()->withAttribute('id', '99'), $handler);

        self::assertSame(['user' => null, 'assignableRoles' => []], $attached);
    }

    #[Test]
    public function attachesTheUserAndAssignableRoles(): void
    {
        $user = new User(
            id   : 5,
            email: 'jane@example.com',
        );

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->exactly(2))
            ->method('handle')
            ->willReturnCallback(static function (MessageInterface $message) use ($user): QueryResult {
                if ($message instanceof FetchUserByIdQuery) {
                    return new QueryResult($message, MessageStatus::Success, $user);
                }

                assert($message instanceof FetchAssignableRolesQuery, description: 'Expected the roles query');

                return new QueryResult($message, MessageStatus::Success, ['Member']);
            });

        $attached = null;

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(
                static function (ServerRequestInterface $request) use (&$attached): ResponseInterface {
                    $attached = $request->getAttribute(UpdateUserModalMiddleware::class);

                    return new HtmlResponse('<form>');
                },
            );

        $actor = $this->createStub(UserInterface::class);
        $actor->method('getRoles')->willReturn(['Administrator']);

        $request = new ServerRequest()->withAttribute('id', '5')
            ->withAttribute(UserInterface::class, $actor);

        $middleware = new UpdateUserModalMiddleware(
            messageBus     : $messageBus,
            assignableRoles: new AssignableRolesProvider($messageBus),
        );

        $response = $middleware->process($request, $handler);

        self::assertSame(['user' => $user, 'assignableRoles' => ['Member']], $attached);
        self::assertSame('<form>', (string) $response->getBody());
    }

    #[Test]
    public function returnsTheHandlerResponseUnchanged(): void
    {
        $messageBus = $this->createStub(MessageBusInterface::class);
        $messageBus->method('handle')
            ->willReturn(new QueryResult(new FetchUserByIdQuery(id: 1), MessageStatus::Failure, null));

        $expected = new HtmlResponse('', 404);

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($expected);

        $middleware = new UpdateUserModalMiddleware(
            messageBus     : $messageBus,
            assignableRoles: new AssignableRolesProvider($messageBus),
        );

        self::assertSame(
            $expected,
            $middleware->process(new ServerRequest()->withAttribute('id', '1'), $handler),
        );
    }
}
