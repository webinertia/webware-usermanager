<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\QueryHandler;

use PhpDb\ResultSet\RowPrototypeResultSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Query\FetchUsers;
use Webware\UserManager\QueryHandler\FetchUsersHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

#[CoversClass(FetchUsersHandler::class)]
#[CoversMethod(FetchUsersHandler::class, '__construct')]
#[CoversMethod(FetchUsersHandler::class, 'handle')]
final class FetchUsersHandlerTest extends TestCase
{
    #[Test]
    public function handleReturnsAllUsers(): void
    {
        $resultSet = new RowPrototypeResultSet(new User());
        $resultSet->initialize([
            ['id' => 1, 'active' => 1],
            ['id' => 2, 'active' => 0],
        ]);

        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findAll')->willReturn($resultSet);

        $handler = new FetchUsersHandler($users);
        $result  = $handler->handle(new FetchUsers());

        self::assertSame(MessageStatus::Success, $result->getStatus());

        $list = $result->getResult();
        self::assertIsArray($list);
        self::assertCount(2, $list);
        self::assertInstanceOf(User::class, $list[0]);
        self::assertInstanceOf(User::class, $list[1]);
    }

    #[Test]
    public function handleReturnsEmptyListWhenNoUsers(): void
    {
        $resultSet = new RowPrototypeResultSet(new User());
        $resultSet->initialize([]);

        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findAll')->willReturn($resultSet);

        $handler = new FetchUsersHandler($users);
        $result  = $handler->handle(new FetchUsers());

        self::assertSame(MessageStatus::Success, $result->getStatus());
        self::assertSame([], $result->getResult());
    }
}
