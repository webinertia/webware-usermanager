<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Query;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Core\Role;
use Webware\UserManager\Query\FetchAssignableRolesQuery;

#[CoversClass(FetchAssignableRolesQuery::class)]
#[CoversMethod(FetchAssignableRolesQuery::class, '__construct')]
final class FetchAssignableRolesQueryTest extends TestCase
{
    #[Test]
    public function constructorAssignsActorRoleId(): void
    {
        $query = new FetchAssignableRolesQuery(actorRoleId: Role::Administrator->value);

        self::assertSame(Role::Administrator->value, $query->actorRoleId);
    }
}
