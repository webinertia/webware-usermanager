<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Auth\AuthenticationResult;
use Webware\UserManager\Auth\AuthenticationStatus;
use Webware\UserManager\Entity\User;

#[CoversClass(AuthenticationResult::class)]
#[CoversMethod(AuthenticationResult::class, '__construct')]
final class AuthenticationResultTest extends TestCase
{
    #[Test]
    public function constructorAssignsStatusAndUser(): void
    {
        $user = new User(id: 1);

        $result = new AuthenticationResult(AuthenticationStatus::Success, $user);

        self::assertSame(AuthenticationStatus::Success, $result->status);
        self::assertSame($user, $result->user);
    }

    #[Test]
    public function constructorDefaultsUserToNull(): void
    {
        $result = new AuthenticationResult(AuthenticationStatus::InvalidCredentials);

        self::assertSame(AuthenticationStatus::InvalidCredentials, $result->status);
        self::assertNull($result->user);
    }
}
