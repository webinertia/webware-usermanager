<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\QueryHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Query\FetchUserById;
use Webware\UserManager\QueryHandler\FetchUserByIdHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

#[CoversClass(FetchUserByIdHandler::class)]
#[CoversMethod(FetchUserByIdHandler::class, '__construct')]
#[CoversMethod(FetchUserByIdHandler::class, 'handle')]
final class FetchUserByIdHandlerTest extends TestCase
{
    #[Test]
    public function handleReturnsFailureWhenUserNotFound(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn(null);

        $handler = new FetchUserByIdHandler($users);
        $result  = $handler->handle(new FetchUserById(id: 7));

        self::assertSame(MessageStatus::Failure, $result->getStatus());
        self::assertNull($result->getResult());
    }

    #[Test]
    public function handleReturnsUserOnSuccess(): void
    {
        $user  = new User(id: 7);
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn($user);

        $handler = new FetchUserByIdHandler($users);
        $result  = $handler->handle(new FetchUserById(id: 7));

        self::assertSame(MessageStatus::Success, $result->getStatus());
        self::assertSame($user, $result->getResult());
    }
}
