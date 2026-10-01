<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Acl;

use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Router\Route;
use Mezzio\Router\RouteCollectorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Webware\Core\Acl\RuleSeed;
use Webware\Core\Acl\RuleType;
use Webware\Core\Role;
use Webware\UserManager\Acl\RuleSeeds;
use Webware\UserManager\Container\Configuration;
use Webware\UserManager\RouteProvider;

use function array_diff;
use function array_map;
use function array_slice;
use function array_unique;
use function array_values;

#[CoversClass(RuleSeeds::class)]
#[CoversMethod(RuleSeeds::class, 'ruleSeeds')]
final class RuleSeedsTest extends TestCase
{
    /**
     * A leaf rule can only match a route that exists, so every seeded leaf must be a
     * name the RouteProvider registers; the two anchors are the nodes they hang from.
     */
    #[Test]
    public function everySeededLeafIsARegisteredRouteName(): void
    {
        /** @var list<string|null> $names */
        $names = [];

        $collector = $this->createStub(RouteCollectorInterface::class);

        foreach (['get', 'post', 'patch', 'delete'] as $method) {
            $collector->method($method)
                ->willReturnCallback(
                    static function (
                        string $path,
                        MiddlewareInterface $middleware,
                        ?string $name = null,
                    ) use (&$names): Route {
                        $names[] = $name;

                        return new Route($path, $middleware, ['GET'], $name);
                    },
                );
        }

        $collector->method('route')
            ->willReturnCallback(
                static function (
                    string $path,
                    MiddlewareInterface $middleware,
                    ?array $methods = null,
                    ?string $name = null,
                ) use (&$names): Route {
                    $names[] = $name;

                    return new Route($path, $middleware, $methods ?? ['GET'], $name);
                },
            );

        $middlewareFactory = $this->createStub(MiddlewareFactoryInterface::class);
        $middlewareFactory->method('prepare')->willReturn($this->createStub(MiddlewareInterface::class));

        new RouteProvider(
            Configuration::getRouteSegment(),
            Configuration::getRouteNamePrefix(),
            Configuration::getAdminRouteSegment('admin'),
            Configuration::getAdminRouteNamePrefix('admin'),
        )->registerRoutes($collector, $middlewareFactory);

        $leaves = [];

        foreach ($this->seeds('admin') as $seed) {
            if (null === $seed->parentResourceId) {
                continue;
            }

            $leaves[] = $seed->resourceId;
        }

        self::assertSame([], array_diff($leaves, $names));
        self::assertContains('admin.user', $names);
    }

    #[Test]
    public function followsTheConfiguredAdminName(): void
    {
        $seeds = $this->seeds('backoffice');

        self::assertSame('backoffice.user', $seeds[18]->resourceId);
        self::assertSame('backoffice.user.create', $seeds[19]->resourceId);
        self::assertSame('backoffice.user', $seeds[19]->parentResourceId);
    }

    #[Test]
    public function givesLogoutItsOwnPairSoMemberIsAllowedAndGuestIsNot(): void
    {
        $seeds = $this->seeds('admin');

        self::assertEquals(
            new RuleSeed(
                type            : RuleType::Deny,
                roleId          : Role::Guest->value,
                resourceId      : 'user.logout.read',
                parentResourceId: 'user',
            ),
            $seeds[2],
        );
        self::assertEquals(
            new RuleSeed(
                type            : RuleType::Allow,
                roleId          : Role::Member->value,
                resourceId      : 'user.logout.read',
                parentResourceId: 'user',
            ),
            $seeds[3],
        );
    }

    #[Test]
    public function grantsAdministratorTheAdminAnchorAndEachChildUnderIt(): void
    {
        $seeds = $this->seeds('admin');

        self::assertEquals(
            new RuleSeed(
                type      : RuleType::Allow,
                roleId    : Role::Administrator->value,
                resourceId: 'admin.user',
            ),
            $seeds[18],
        );
        self::assertSame(
            ['admin.user.create', 'admin.user.update', 'admin.user.update.modal', 'admin.user.toggle.update'],
            array_map(static fn(RuleSeed $seed): string => $seed->resourceId, array_slice(
                array : $seeds,
                offset: 19,
            )),
        );

        foreach (array_slice(
            array : $seeds,
            offset: 19,
        ) as $child) {
            self::assertSame(RuleType::Allow, $child->type);
            self::assertSame(Role::Administrator->value, $child->roleId);
            self::assertSame('admin.user', $child->parentResourceId);
        }
    }

    #[Test]
    public function grantsGuestThePublicAnchorAndDeniesMember(): void
    {
        $seeds = $this->seeds('admin');

        self::assertEquals(
            new RuleSeed(
                type      : RuleType::Allow,
                roleId    : Role::Guest->value,
                resourceId: 'user',
            ),
            $seeds[0],
        );
        self::assertEquals(
            new RuleSeed(
                type      : RuleType::Deny,
                roleId    : Role::Member->value,
                resourceId: 'user',
            ),
            $seeds[1],
        );
    }

    #[Test]
    public function repeatsTheAnchorPairUnderEachRemainingPublicRoute(): void
    {
        $seeds = array_slice(
            array : $this->seeds('admin'),
            offset: 4,
            length: 14,
        );

        self::assertSame(
            [
                'user.session.read',
                'user.session.create',
                'user.register.read',
                'user.register.create',
                'user.verify.email.read',
                'user.resend.verification.read',
                'user.resend.verification.create',
            ],
            array_values(array_unique(array_map(
                static fn(RuleSeed $seed): string => $seed->resourceId,
                $seeds,
            ))),
        );

        foreach ($seeds as $index => $seed) {
            self::assertSame('user', $seed->parentResourceId);
            self::assertSame(0 === ($index % 2) ? RuleType::Allow : RuleType::Deny, $seed->type);
            self::assertSame(0 === ($index % 2) ? Role::Guest->value : Role::Member->value, $seed->roleId);
        }
    }

    /**
     * @return list<RuleSeed>
     */
    private function seeds(string $adminName): array
    {
        return new RuleSeeds()->ruleSeeds($adminName);
    }
}
