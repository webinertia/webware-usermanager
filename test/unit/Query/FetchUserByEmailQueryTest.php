<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Query;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Query\FetchUserByEmailQuery;

#[CoversClass(FetchUserByEmailQuery::class)]
#[CoversMethod(FetchUserByEmailQuery::class, '__construct')]
final class FetchUserByEmailQueryTest extends TestCase
{
    #[Test]
    public function constructorAssignsEmail(): void
    {
        $query = new FetchUserByEmailQuery(email: 'jane@example.com');

        self::assertSame('jane@example.com', $query->email);
    }
}
