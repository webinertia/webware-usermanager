<?php

declare(strict_types=1);

namespace Webware\UserManager\Acl;

use Override;
use Webware\Core\Acl\RuleSeed;
use Webware\Core\Acl\RuleSeedProviderInterface;
use Webware\Core\Acl\RuleType;
use Webware\Core\Role;
use Webware\UserManager\Container\Configuration;

use function rtrim;

/**
 * The policy for the routes this package owns.
 *
 * Public routes hang from the `user` anchor: Guest may reach them all, Member
 * is denied them all, and the one route a Member needs (logout) carries its own
 * pair of rows, because a rule on the route itself beats the anchor's. Every other
 * public route repeats the anchor's pair under its own name, since the ACL
 * administration screens list a route without a row of its own as unprotected. The
 * admin routes hang from the `admin.user` anchor, which Administrator is
 * granted, with each child naming the anchor as its parent so it inherits from
 * it and nests beneath it in the ACL administration screens.
 *
 * @internal
 */
final readonly class RuleSeeds implements RuleSeedProviderInterface
{
    private const array PUBLIC_CHILDREN = [
        'session.read',
        'session.create',
        'register.read',
        'register.create',
        'verify.email.read',
        'set.password.read',
        'set.password.create',
        'resend.verification.read',
        'resend.verification.create',
    ];

    private const array ADMIN_CHILDREN = [
        'create',
        'create.modal',
        'update',
        'update.modal',
        'toggle.update',
    ];

    /**
     * @return list<RuleSeed>
     */
    #[Override]
    public function ruleSeeds(string $adminName): array
    {
        $public     = Configuration::getRouteNamePrefix();
        $publicRoot = rtrim(
            string    : $public,
            characters: '.',
        );
        $admin     = Configuration::getAdminRouteNamePrefix($adminName);
        $adminRoot = rtrim(
            string    : $admin,
            characters: '.',
        );
        $logout = "{$public}logout.read";

        $seeds = [
            new RuleSeed(
                type      : RuleType::Allow,
                roleId    : Role::Guest->value,
                resourceId: $publicRoot,
            ),
            new RuleSeed(
                type      : RuleType::Deny,
                roleId    : Role::Member->value,
                resourceId: $publicRoot,
            ),
            new RuleSeed(
                type            : RuleType::Deny,
                roleId          : Role::Guest->value,
                resourceId      : $logout,
                parentResourceId: $publicRoot,
            ),
            new RuleSeed(
                type            : RuleType::Allow,
                roleId          : Role::Member->value,
                resourceId      : $logout,
                parentResourceId: $publicRoot,
            ),
        ];

        foreach (self::PUBLIC_CHILDREN as $child) {
            $seeds[] = new RuleSeed(
                type            : RuleType::Allow,
                roleId          : Role::Guest->value,
                resourceId      : $public . $child,
                parentResourceId: $publicRoot,
            );
            $seeds[] = new RuleSeed(
                type            : RuleType::Deny,
                roleId          : Role::Member->value,
                resourceId      : $public . $child,
                parentResourceId: $publicRoot,
            );
        }

        $seeds[] = new RuleSeed(
            type      : RuleType::Allow,
            roleId    : Role::Administrator->value,
            resourceId: $adminRoot,
        );

        foreach (self::ADMIN_CHILDREN as $child) {
            $seeds[] = new RuleSeed(
                type            : RuleType::Allow,
                roleId          : Role::Administrator->value,
                resourceId      : $admin . $child,
                parentResourceId: $adminRoot,
            );
        }

        return $seeds;
    }
}
