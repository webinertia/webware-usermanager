<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Auth\AuthenticationStatus;

use function array_map;

#[CoversClass(AuthenticationStatus::class)]
final class AuthenticationStatusTest extends TestCase
{
    #[Test]
    public function definesExpectedCases(): void
    {
        self::assertSame(
            ['Success', 'InvalidCredentials', 'NotActive'],
            array_map(
                static fn(AuthenticationStatus $status): string => $status->name,
                AuthenticationStatus::cases(),
            ),
        );
    }
}
