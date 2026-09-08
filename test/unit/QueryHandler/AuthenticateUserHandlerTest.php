<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\QueryHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Auth\AuthenticationResult;
use Webware\UserManager\Auth\AuthenticationStatus;
use Webware\UserManager\Query\AuthenticateUser;
use Webware\UserManager\QueryHandler\AuthenticateUserHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

use function bin2hex;
use function random_bytes;

#[CoversClass(AuthenticateUserHandler::class)]
#[CoversMethod(AuthenticateUserHandler::class, '__construct')]
#[CoversMethod(AuthenticateUserHandler::class, 'handle')]
final class AuthenticateUserHandlerTest extends TestCase
{
    #[Test]
    public function handleDelegatesToRepository(): void
    {
        $authResult = new AuthenticationResult(AuthenticationStatus::Success);
        $users      = $this->createStub(UserRepositoryInterface::class);
        $users->method('authenticate')->willReturn($authResult);

        $handler = new AuthenticateUserHandler($users);
        $result  = $handler->handle(
            new AuthenticateUser(
                credential: 'jane@example.com',
                password  : bin2hex(random_bytes(16)),
            ),
        );

        self::assertSame(MessageStatus::Success, $result->getStatus());
        self::assertSame($authResult, $result->getResult());
    }
}
