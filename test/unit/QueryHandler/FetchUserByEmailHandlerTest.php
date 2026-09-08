<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\QueryHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Query\FetchUserByEmail;
use Webware\UserManager\QueryHandler\FetchUserByEmailHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

#[CoversClass(FetchUserByEmailHandler::class)]
#[CoversMethod(FetchUserByEmailHandler::class, '__construct')]
#[CoversMethod(FetchUserByEmailHandler::class, 'handle')]
final class FetchUserByEmailHandlerTest extends TestCase
{
    #[Test]
    public function handleReturnsFailureWhenUserNotFound(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn(null);

        $handler = new FetchUserByEmailHandler($users);
        $result  = $handler->handle(new FetchUserByEmail(email: 'jane@example.com'));

        self::assertSame(MessageStatus::Failure, $result->getStatus());
        self::assertNull($result->getResult());
    }

    #[Test]
    public function handleReturnsUserOnSuccess(): void
    {
        $user  = new User(id: 7);
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn($user);

        $handler = new FetchUserByEmailHandler($users);
        $result  = $handler->handle(new FetchUserByEmail(email: 'jane@example.com'));

        self::assertSame(MessageStatus::Success, $result->getStatus());
        self::assertSame($user, $result->getResult());
    }
}
