<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Query;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Query\FetchUserByEmail;

#[CoversClass(FetchUserByEmail::class)]
#[CoversMethod(FetchUserByEmail::class, '__construct')]
final class FetchUserByEmailTest extends TestCase
{
    #[Test]
    public function constructorAssignsEmail(): void
    {
        $query = new FetchUserByEmail(email: 'jane@example.com');

        self::assertSame('jane@example.com', $query->email);
    }
}
