<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Query;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Query\FetchUsersQuery;

#[CoversClass(FetchUsersQuery::class)]
final class FetchUsersQueryTest extends TestCase
{
    #[Test]
    public function canBeInstantiated(): void
    {
        self::assertInstanceOf(FetchUsersQuery::class, new FetchUsersQuery());
    }
}
