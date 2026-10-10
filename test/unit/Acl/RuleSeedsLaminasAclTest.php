<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Acl;

use Laminas\Permissions\Acl\Acl;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Core\Role;
use Webware\UserManager\Acl\RuleSeeds;
use WebwareTest\UserManager\Support\SeededAcl;

use function array_combine;
use function array_map;

/**
 * Loads the seeds into a real Laminas ACL the way the rules table is loaded:
 * each row registers its resource under its parent, then the rule is applied.
 * This is what settles whether a rule on a route beats the rule on its anchor.
 */
#[CoversClass(RuleSeeds::class)]
#[CoversMethod(RuleSeeds::class, 'ruleSeeds')]
final class RuleSeedsLaminasAclTest extends TestCase
{
    private const array PUBLIC_ROUTES = [
        'user.session.read',
        'user.session.create',
        'user.register.read',
        'user.register.create',
        'user.verify.email.read',
        'user.set.password.read',
        'user.set.password.create',
        'user.resend.verification.read',
        'user.resend.verification.create',
    ];

    private const array ADMIN_ROUTES = [
        'admin.user',
        'admin.user.create',
        'admin.user.update',
        'admin.user.update.modal',
        'admin.user.toggle.update',
    ];

    /**
     * @return array<string, array{string}>
     */
    public static function adminRoutes(): array
    {
        return array_combine(self::ADMIN_ROUTES, array_map(static fn(string $route): array => [
            $route,
        ], self::ADMIN_ROUTES));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function publicRoutes(): array
    {
        return array_combine(
            self::PUBLIC_ROUTES,
            array_map(static fn(string $route): array => [$route], self::PUBLIC_ROUTES),
        );
    }

    #[Test]
    #[DataProvider('adminRoutes')]
    public function administratorIsAllowedTheAdminAnchorAndEachChild(string $route): void
    {
        self::assertTrue($this->acl()->isAllowed(Role::Administrator->value, $route));
    }

    #[Test]
    #[DataProvider('publicRoutes')]
    public function guestReachesEveryPublicRouteThroughTheAnchor(string $route): void
    {
        self::assertTrue($this->acl()->isAllowed(Role::Guest->value, $route));
    }

    #[Test]
    public function logoutIsAllowedToMemberDespiteTheAnchorDeny(): void
    {
        self::assertTrue($this->acl()->isAllowed(Role::Member->value, 'user.logout.read'));
    }

    #[Test]
    public function logoutIsDeniedToGuestDespiteTheAnchorAllow(): void
    {
        self::assertFalse($this->acl()->isAllowed(Role::Guest->value, 'user.logout.read'));
    }

    #[Test]
    #[DataProvider('publicRoutes')]
    public function memberIsDeniedEveryPublicRouteExceptLogout(string $route): void
    {
        self::assertFalse($this->acl()->isAllowed(Role::Member->value, $route));
    }

    #[Test]
    public function memberIsDeniedTheAdminRoutes(): void
    {
        self::assertFalse($this->acl()->isAllowed(Role::Member->value, 'admin.user.create'));
    }

    private function acl(): Acl
    {
        return SeededAcl::from(
            seeds            : new RuleSeeds()->ruleSeeds('admin'),
            routesWithoutRows: [],
            anchor           : 'user',
        );
    }
}
