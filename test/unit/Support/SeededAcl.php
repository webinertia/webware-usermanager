<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Support;

use Laminas\Permissions\Acl\Acl;
use Webware\Core\Acl\RuleSeed;
use Webware\Core\Role;

use function array_keys;
use function array_map;

/**
 * Loads rule seeds into a real Laminas ACL the way the rules table is loaded:
 * each row registers its resource under its parent, then the rule is applied.
 * Routes that carry no row of their own attach to an anchor, as they do when
 * the ACL is built from the table.
 */
final class SeededAcl
{
    /**
     * @param list<RuleSeed> $seeds
     * @param list<string>   $routesWithoutRows
     */
    public static function from(array $seeds, array $routesWithoutRows, string $anchor): Acl
    {
        $acl = new Acl();

        array_map(
            static fn(array $role): Acl => $acl->addRole($role['roleId'], $role['parentIds']),
            Role::getRoles(),
        );

        $parents = [];

        array_map(
            static function (RuleSeed $seed) use (&$parents): void {
                $parents[$seed->resourceId] = $seed->parentResourceId;
            },
            $seeds,
        );

        array_map(
            $acl->addResource(...),
            array_keys($parents),
            $parents,
        );

        array_map(
            static fn(string $route): Acl => $acl->addResource($route, $anchor),
            $routesWithoutRows,
        );

        array_map(
            static fn(RuleSeed $seed): Acl => $acl->setRule(
                Acl::OP_ADD,
                $seed->type->toAclConstant(),
                $seed->roleId,
                $seed->resourceId,
            ),
            $seeds,
        );

        return $acl;
    }
}
