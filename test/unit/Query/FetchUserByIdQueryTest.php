<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Query;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Query\FetchUserByIdQuery;

#[CoversClass(FetchUserByIdQuery::class)]
#[CoversMethod(FetchUserByIdQuery::class, '__construct')]
final class FetchUserByIdQueryTest extends TestCase
{
    #[Test]
    public function constructorAssignsId(): void
    {
        $query = new FetchUserByIdQuery(id: 7);

        self::assertSame(7, $query->id);
    }
}
