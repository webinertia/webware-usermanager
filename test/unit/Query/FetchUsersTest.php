<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Query;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Query\FetchUsers;

#[CoversClass(FetchUsers::class)]
final class FetchUsersTest extends TestCase
{
    #[Test]
    public function canBeInstantiated(): void
    {
        self::assertInstanceOf(FetchUsers::class, new FetchUsers());
    }
}
