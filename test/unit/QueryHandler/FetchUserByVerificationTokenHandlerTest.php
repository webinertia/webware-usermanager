<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\QueryHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Query\FetchUserByVerificationTokenQuery;
use Webware\UserManager\QueryHandler\FetchUserByVerificationTokenHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

use function bin2hex;
use function random_bytes;

#[CoversClass(FetchUserByVerificationTokenHandler::class)]
#[CoversMethod(FetchUserByVerificationTokenHandler::class, '__construct')]
#[CoversMethod(FetchUserByVerificationTokenHandler::class, 'handle')]
final class FetchUserByVerificationTokenHandlerTest extends TestCase
{
    #[Test]
    public function handleReturnsFailureWhenUserNotFound(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByVerificationToken')->willReturn(null);

        $handler = new FetchUserByVerificationTokenHandler($users);
        $result  = $handler->handle(new FetchUserByVerificationTokenQuery(token: bin2hex(random_bytes(16))));

        self::assertSame(MessageStatus::Failure, $result->getStatus());
        self::assertNull($result->getResult());
    }

    #[Test]
    public function handleReturnsUserOnSuccess(): void
    {
        $user  = new User(id: 7);
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByVerificationToken')->willReturn($user);

        $handler = new FetchUserByVerificationTokenHandler($users);
        $result  = $handler->handle(new FetchUserByVerificationTokenQuery(token: bin2hex(random_bytes(16))));

        self::assertSame(MessageStatus::Success, $result->getStatus());
        self::assertSame($user, $result->getResult());
    }
}
