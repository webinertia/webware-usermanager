<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Query;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Query\FetchUserByVerificationToken;

use function bin2hex;
use function random_bytes;

#[CoversClass(FetchUserByVerificationToken::class)]
#[CoversMethod(FetchUserByVerificationToken::class, '__construct')]
final class FetchUserByVerificationTokenTest extends TestCase
{
    #[Test]
    public function constructorAssignsToken(): void
    {
        $token = bin2hex(random_bytes(16));

        $query = new FetchUserByVerificationToken(token: $token);

        self::assertSame($token, $query->token);
    }
}
