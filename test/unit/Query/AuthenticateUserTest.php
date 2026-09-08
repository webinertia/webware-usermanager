<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Query;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Query\AuthenticateUser;

use function bin2hex;
use function random_bytes;

#[CoversClass(AuthenticateUser::class)]
#[CoversMethod(AuthenticateUser::class, '__construct')]
final class AuthenticateUserTest extends TestCase
{
    #[Test]
    public function constructorAssignsCredentialAndPassword(): void
    {
        $password = bin2hex(random_bytes(16));

        $query = new AuthenticateUser(
            credential: 'jane@example.com',
            password  : $password,
        );

        self::assertSame('jane@example.com', $query->credential);
        self::assertSame($password, $query->password);
    }

    #[Test]
    public function passwordDefaultsToNull(): void
    {
        $query = new AuthenticateUser(credential: 'jane@example.com');

        self::assertSame('jane@example.com', $query->credential);
        self::assertNull($query->password);
    }
}
