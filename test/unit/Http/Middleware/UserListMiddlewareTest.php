<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Middleware;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Http\Middleware\UserListMiddleware;
use Webware\UserManager\Query\FetchUsersQuery;

#[CoversClass(UserListMiddleware::class)]
#[CoversMethod(UserListMiddleware::class, '__construct')]
#[CoversMethod(UserListMiddleware::class, 'process')]
final class UserListMiddlewareTest extends TestCase
{
    #[Test]
    public function attachesTheFetchedUsersToTheRequest(): void
    {
        $users = [new User(id: 1), new User(id: 2)];

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(FetchUsersQuery::class))
            ->willReturn(new QueryResult(new FetchUsersQuery(), MessageStatus::Success, $users));

        $attached = null;

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(
                static function (ServerRequestInterface $request) use (&$attached): ResponseInterface {
                    $attached = $request->getAttribute(UserListMiddleware::class);

                    return new HtmlResponse('<ul>');
                },
            );

        $middleware = new UserListMiddleware(messageBus: $messageBus);

        $response = $middleware->process(new ServerRequest(), $handler);

        self::assertSame(['users' => $users], $attached);
        self::assertSame('<ul>', (string) $response->getBody());
    }

    #[Test]
    public function returnsTheHandlerResponseUnchanged(): void
    {
        $messageBus = $this->createStub(MessageBusInterface::class);
        $messageBus->method('handle')
            ->willReturn(new QueryResult(new FetchUsersQuery(), MessageStatus::Success, []));

        $expected = new HtmlResponse('<ul>', 201);

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($expected);

        $middleware = new UserListMiddleware(messageBus: $messageBus);

        self::assertSame($expected, $middleware->process(new ServerRequest(), $handler));
    }
}
