<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\QueryHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Core\AclInterface;
use Webware\Core\Role;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Query\FetchAssignableRolesQuery;
use Webware\UserManager\QueryHandler\FetchAssignableRolesHandler;

#[CoversClass(FetchAssignableRolesHandler::class)]
#[CoversMethod(FetchAssignableRolesHandler::class, '__construct')]
#[CoversMethod(FetchAssignableRolesHandler::class, 'handle')]
final class FetchAssignableRolesHandlerTest extends TestCase
{
    /**
     * The default hierarchy, in registry order: Guest → Member → Administrator → Developer.
     *
     * @return array<string, string[]>
     */
    private static function defaultRegistry(): array
    {
        return [
            Role::Guest->value         => [],
            Role::Member->value        => [Role::Guest->value],
            Role::Administrator->value => [Role::Member->value],
            Role::Developer->value     => [Role::Administrator->value],
        ];
    }

    #[Test]
    public function administratorMayAssignItsOwnLineageExceptGuest(): void
    {
        self::assertSame(
            [Role::Member->value, Role::Administrator->value],
            $this->assignableRoles(
                actorRoleId: Role::Administrator->value,
                registry   : self::defaultRegistry(),
            ),
        );
    }

    #[Test]
    public function developerMayAssignEveryRoleInItsLineageExceptGuest(): void
    {
        self::assertSame(
            [Role::Member->value, Role::Administrator->value, Role::Developer->value],
            $this->assignableRoles(
                actorRoleId: Role::Developer->value,
                registry   : self::defaultRegistry(),
            ),
        );
    }

    #[Test]
    public function guestMayAssignNothing(): void
    {
        self::assertSame(
            [],
            $this->assignableRoles(
                actorRoleId: Role::Guest->value,
                registry   : self::defaultRegistry(),
            ),
        );
    }

    #[Test]
    public function ignoresAParentRoleThatIsNotInTheRegistry(): void
    {
        $registry = [
            Role::Guest->value         => [],
            Role::Member->value        => [Role::Guest->value],
            Role::Administrator->value => [Role::Member->value, 'RetiredRole'],
        ];

        self::assertSame(
            [Role::Member->value, Role::Administrator->value],
            $this->assignableRoles(
                actorRoleId: Role::Administrator->value,
                registry   : $registry,
            ),
        );
    }

    #[Test]
    public function memberMayAssignOnlyItsOwnRole(): void
    {
        self::assertSame(
            [Role::Member->value],
            $this->assignableRoles(
                actorRoleId: Role::Member->value,
                registry   : self::defaultRegistry(),
            ),
        );
    }

    #[Test]
    public function returnsTheLineageInRegistryKeyOrder(): void
    {
        $registry = [
            Role::Developer->value     => [Role::Administrator->value],
            Role::Administrator->value => [Role::Member->value],
            Role::Member->value        => [Role::Guest->value],
            Role::Guest->value         => [],
        ];

        self::assertSame(
            [Role::Developer->value, Role::Administrator->value, Role::Member->value],
            $this->assignableRoles(
                actorRoleId: Role::Developer->value,
                registry   : $registry,
            ),
        );
    }

    #[Test]
    public function terminatesOnACyclicRegistry(): void
    {
        $registry = [
            Role::Guest->value  => [],
            Role::Member->value => [Role::Guest->value],
            'Sales'             => ['Warehouse'],
            'Warehouse'         => ['CreditManager'],
            'CreditManager'     => ['Sales'],
        ];

        self::assertSame(
            ['Sales', 'Warehouse', 'CreditManager'],
            $this->assignableRoles(
                actorRoleId: 'Sales',
                registry   : $registry,
            ),
        );
    }

    #[Test]
    public function unknownActorRoleMayAssignNothing(): void
    {
        self::assertSame(
            [],
            $this->assignableRoles(
                actorRoleId: 'NoSuchRole',
                registry   : self::defaultRegistry(),
            ),
        );
    }

    #[Test]
    public function walksEveryParentBranchOfAnImsStyleRegistry(): void
    {
        $registry = [
            Role::Guest->value         => [],
            Role::Member->value        => [Role::Guest->value],
            'Sales'                    => [Role::Member->value],
            'Warehouse'                => [Role::Member->value],
            'CreditManager'            => [Role::Member->value],
            'Manager'                  => ['Sales', 'Warehouse', 'CreditManager'],
            Role::Administrator->value => ['Manager'],
            Role::Developer->value     => [Role::Administrator->value],
        ];

        self::assertSame(
            ['Member', 'Sales', 'Warehouse', 'CreditManager', 'Manager'],
            $this->assignableRoles(
                actorRoleId: 'Manager',
                registry   : $registry,
            ),
        );

        self::assertSame(
            ['Member', 'Sales', 'Warehouse', 'CreditManager', 'Manager', Role::Administrator->value],
            $this->assignableRoles(
                actorRoleId: Role::Administrator->value,
                registry   : $registry,
            ),
        );
    }

    #[Test]
    public function walksEveryParentBranchWhenARoleListsARedundantAncestor(): void
    {
        $registry = [
            Role::Guest->value  => [],
            Role::Member->value => [Role::Guest->value],
            'Sales'             => [Role::Member->value],
            'Warehouse'         => [Role::Member->value],
            'Manager'           => ['Sales', Role::Member->value, 'Warehouse'],
        ];

        self::assertSame(
            ['Member', 'Sales', 'Warehouse', 'Manager'],
            $this->assignableRoles(
                actorRoleId: 'Manager',
                registry   : $registry,
            ),
        );
    }

    /**
     * @param array<string, string[]> $registry
     */
    private function assignableRoles(string $actorRoleId, array $registry): mixed
    {
        $acl = $this->createStub(AclInterface::class);
        $acl->method('getRoles')->willReturn($registry);

        $result = new FetchAssignableRolesHandler(acl: $acl)->handle(
            new FetchAssignableRolesQuery(actorRoleId: $actorRoleId),
        );

        self::assertSame(MessageStatus::Success, $result->getStatus());

        return $result->getResult();
    }
}
