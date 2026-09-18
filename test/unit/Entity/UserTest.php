<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Core\UserInterface;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Exception\UnassignedIdentityException;

use function bin2hex;
use function password_verify;
use function random_bytes;

#[CoversClass(User::class)]
#[CoversClass(UnassignedIdentityException::class)]
#[CoversMethod(User::class, '__construct')]
#[CoversMethod(User::class, 'getDetail')]
#[CoversMethod(User::class, 'getDetails')]
#[CoversMethod(User::class, 'getIdentity')]
#[CoversMethod(User::class, 'getOwnerId')]
#[CoversMethod(User::class, 'getResourceId')]
#[CoversMethod(User::class, 'getRoleId')]
#[CoversMethod(User::class, 'getRoles')]
#[CoversMethod(User::class, 'populate')]
#[CoversMethod(User::class, 'toArray')]
#[CoversMethod(User::class, 'withActive')]
#[CoversMethod(User::class, 'withDetail')]
#[CoversMethod(User::class, 'withEmail')]
#[CoversMethod(User::class, 'withFirstName')]
#[CoversMethod(User::class, 'withIdentity')]
#[CoversMethod(User::class, 'withLastName')]
#[CoversMethod(User::class, 'withPasswordHash')]
#[CoversMethod(User::class, 'withRoleId')]
#[CoversMethod(User::class, '__invoke')]
final class UserTest extends TestCase
{
    #[Test]
    public function constructorCastsActiveToBool(): void
    {
        static::assertTrue(new User(active: 1)->active);
        static::assertFalse(new User(active: 0)->active);
    }

    #[Test]
    public function constructorCastsIdStringToInt(): void
    {
        static::assertSame(42, new User(id: '42')->id);
    }

    #[Test]
    public function constructorDecodesDetailsJsonString(): void
    {
        static::assertSame(['a' => 1], new User(details: '{"a":1}')->details);
    }

    #[Test]
    public function constructorLowercasesEmail(): void
    {
        static::assertSame('jane@example.com', new User(email: 'JANE@EXAMPLE.COM')->email);
    }

    #[Test]
    public function constructorStoresRoleIdString(): void
    {
        static::assertSame('Member', new User(roleId: 'Member')->roleId);
    }

    #[Test]
    public function getDetailAndGetDetailsExposeDetails(): void
    {
        $user = new User(details: ['storeId' => 207]);

        static::assertSame(207, $user->getDetail('storeId'));
        static::assertSame(['storeId' => 207], $user->getDetails());
    }

    #[Test]
    public function getIdentityReturnsEmail(): void
    {
        static::assertSame('jane@example.com', new User(email: 'jane@example.com')->getIdentity());
    }

    #[Test]
    public function getIdentityThrowsWhenNonGuestCarriesNoEmail(): void
    {
        $this->expectException(UnassignedIdentityException::class);

        new User(roleId: 'Member')->getIdentity();
    }

    #[Test]
    public function getOwnerIdReturnsId(): void
    {
        static::assertSame(7, new User(id: 7)->getOwnerId());
    }

    #[Test]
    public function getResourceIdReturnsUser(): void
    {
        static::assertSame('user', new User()->getResourceId());
    }

    #[Test]
    public function getRoleIdAndRolesExposeRoleId(): void
    {
        $user = new User(roleId: 'Member');

        static::assertSame('Member', $user->getRoleId());
        static::assertSame(['Member'], $user->getRoles());
    }

    #[Test]
    public function invokePopulatesWithRowData(): void
    {
        $user = new User();

        $populated = $user(['id' => 3, 'email' => 'C@EXAMPLE.COM']);

        static::assertNotSame($user, $populated);
        static::assertSame(3, $populated->getOwnerId());
        static::assertSame('c@example.com', $populated->getIdentity());
    }

    #[Test]
    public function invokeWithoutDataReturnsNewInstance(): void
    {
        $user = new User();

        $instance = $user();

        static::assertNotSame($user, $instance);
        static::assertNull($instance->getOwnerId());
        static::assertSame(UserInterface::GUEST_ROLE, $instance->getIdentity());
    }

    #[Test]
    public function populateReturnsNewInstance(): void
    {
        $user = new User(
            id   : 1,
            email: 'a@example.com',
        );
        $populated = $user->populate(['id' => 2, 'email' => 'B@EXAMPLE.COM']);

        static::assertNotSame($user, $populated);
        static::assertSame(2, $populated->id);
        static::assertSame('b@example.com', $populated->email);
    }

    #[Test]
    public function toArrayReturnsPropertyMap(): void
    {
        $user = new User(
            id       : 1,
            firstName: 'Jane',
        );

        $array = $user->toArray();

        static::assertIsArray($array);
        static::assertSame(1, $array['id']);
        static::assertSame('Jane', $array['firstName']);
    }

    #[Test]
    public function withActiveReturnsNewInstance(): void
    {
        $user = new User(active: false);

        $clone = $user->withActive(true);

        static::assertNotSame($user, $clone);
        static::assertFalse($user->active);
        static::assertTrue($clone->active);
    }

    #[Test]
    public function withDetailMergesDetails(): void
    {
        $user = new User(details: ['a' => 1]);

        $clone = $user->withDetail('b', 2);

        static::assertNotSame($user, $clone);
        static::assertSame(['a' => 1], $user->details);
        static::assertSame(['a' => 1, 'b' => 2], $clone->details);
    }

    #[Test]
    public function withDetailMergesIntoNullDetails(): void
    {
        $user = new User();

        $clone = $user->withDetail('b', 2);

        static::assertSame(['b' => 2], $clone->details);
    }

    #[Test]
    public function withEmailLowercasesAndReturnsNewInstance(): void
    {
        $user = new User(email: 'jane@example.com');

        $clone = $user->withEmail('NEW@EXAMPLE.COM');

        static::assertNotSame($user, $clone);
        static::assertSame('jane@example.com', $user->email);
        static::assertSame('new@example.com', $clone->email);
    }

    #[Test]
    public function withFirstNameReturnsNewInstance(): void
    {
        $clone = new User(firstName: 'Jane')->withFirstName('Janet');

        static::assertSame('Janet', $clone->firstName);
    }

    #[Test]
    public function withIdentityReturnsNewInstance(): void
    {
        $user = new User(id: 1);

        $clone = $user->withIdentity('jane@example.com');

        static::assertNotSame($user, $clone);
        static::assertSame('jane@example.com', $clone->getIdentity());
        static::assertSame(1, $clone->id);
    }

    #[Test]
    public function withLastNameReturnsNewInstance(): void
    {
        $clone = new User(lastName: 'Doe')->withLastName('Smith');

        static::assertSame('Smith', $clone->lastName);
    }

    #[Test]
    public function withPasswordHashHashesPlaintext(): void
    {
        $plaintext = bin2hex(random_bytes(16));

        $clone = new User()->withPasswordHash($plaintext);

        static::assertNotSame($plaintext, $clone->passwordHash);
        static::assertTrue(password_verify($plaintext, $clone->passwordHash));
    }

    #[Test]
    public function withRoleIdReplacesDefaultRole(): void
    {
        $clone = new User()->withRoleId('Administrator');

        static::assertSame('Administrator', $clone->roleId);
    }

    #[Test]
    public function withRoleIdReplacesStringRole(): void
    {
        $clone = new User(roleId: 'Member')->withRoleId('Administrator');

        static::assertSame('Administrator', $clone->roleId);
    }
}
