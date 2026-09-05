# UserInterface Contract

`Webware\Core\UserInterface` is the **canonical user identity type** for the
webware package ecosystem. It lives in
[`webware/webware-core`](https://github.com/webinertia/webware-core) — the
shared contracts package — alongside `Webware\Core\AclInterface`.

> **Why these contracts live in `webware-core`**  
> `UserInterface` (originally `Webware\UserManager\UserInterface`) and
> `AclInterface` (originally `Webware\Acl\AclInterface`) were first built inside
> the inventory-management-system application, then split out into their own
> vendor packages. `webware-usermanager` produces the user and `webware-acl`
> consumes it for role and ownership checks, so keeping the contract in either
> package created a circular dependency between two tightly intertwined
> components. Moving both contracts to `webware-core` — which sits below both
> packages in the dependency graph — gives every package a single home for the
> shared types without usermanager and acl depending on each other.

It extends the three Laminas ACL interfaces required for role-based and
ownership-based access control, plus `PhpDb\ResultSet\RowPrototypeInterface`
for row hydration:

```php
namespace Webware\Core;

use Laminas\Permissions\Acl\ProprietaryInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use PhpDb\ResultSet\RowPrototypeInterface;

/**
 * @api
 */
interface UserInterface extends
    RoleInterface,
    ResourceInterface,
    ProprietaryInterface,
    RowPrototypeInterface
{
    final public const string GUEST_ROLE = 'Guest';
    public const string DATETIME_FORMAT = 'Y-m-d H:i:s';

    public int|string|null $id { get; }

    public function getDetail(string $name, mixed $default = null): mixed;

    /** @return array<string, mixed>|null */
    public function getDetails(): ?array;

    public function getIdentity(): ?string;

    /** @return RoleInterface[]|string[]|null */
    public function getRoles(): ?array;

    /** @return static */
    public function withId(int|string|null $id): static;
}
```

---

## Why this interface exists

`Mezzio\Authentication\UserInterface` only covers identity (`getIdentity()`,
`getRoles()`, `getDetail()`), and its return types (`string`, `iterable`) are
tighter than this ecosystem needs (`?string`, `?array`, `mixed`). Rather than
extend it, `Webware\Core\UserInterface` **mirrors** the Mezzio identity
accessors (`getIdentity()`, `getRoles()`, `getDetail()`, `getDetails()`) so it
drops into Mezzio authentication code naturally, while also carrying what
Laminas ACL needs:

| Interface | Provided by | Required for |
|---|---|---|
| `RoleInterface` | `laminas/laminas-permissions-acl` | `$acl->isAllowed($user, ...)` |
| `ResourceInterface` | `laminas/laminas-permissions-acl` | User-profile ownership assertion |
| `ProprietaryInterface` | `laminas/laminas-permissions-acl` | `getOwnerId()` — used by `OwnershipAssertion` |
| `RowPrototypeInterface` | `php-db/phpdb` | Row hydration (`populate()` / `toArray()`) |

`GUEST_ROLE` (`'Guest'`) is the canonical role id for anonymous users. There
is no dedicated `isGuest()` method — consumers compare the user's `getRoleId()`
against `UserInterface::GUEST_ROLE`.

Every concrete user entity produced by the authentication layer **must**
implement this interface so that ACL checks, ownership assertions, and row
hydration work without additional type casting.

---

## The ACL contract sits beside it

`Webware\Core\AclInterface` moved to `webware-core` at the same time, for the
same reason. It extends `Laminas\Permissions\Acl\AclInterface` and adds the
ecosystem-specific surface:

```php
namespace Webware\Core;

use Laminas\Permissions\Acl\AclInterface as LaminasAclInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * @api
 */
interface AclInterface extends LaminasAclInterface
{
    final public const string DEVELOPER_ROLE_ID = 'Developer';

    public function getResourceParentId(string $resourceId): ?string;

    /** @return array<string, string[]> */
    public function getRoles(): array;

    public function isAllowedRoute(
        ?UserInterface $user,
        ResourceInterface $resource,
    ): bool;
}
```

`isAllowedRoute()` is fail-closed and takes the `UserInterface` aggregate
object directly (not decomposed role strings), so assertions such as
`OwnershipAssertion` receive a role that satisfies `ProprietaryInterface`.

---

## Relationship to Mezzio's `UserInterface`

`Webware\Core\UserInterface` deliberately **mirrors** — rather than extends —
`Mezzio\Authentication\UserInterface`. The looser return types mean the two
interfaces cannot be aliased to a single implementation, and `webware-usermanager`
owns the identity flow directly (`IdentityMiddleware` reads and writes the
session under `UserInterface::class`). No host-application DI alias between the
Mezzio and Webware interfaces is required.

---

## Concrete class requirements

Any class used as the concrete implementation must:

1. Implement `Webware\Core\UserInterface` (satisfies all four parent interfaces above).
2. `getRoleId(): string` — return the user's primary role string (e.g.
   `'member'`); guests return `UserInterface::GUEST_ROLE`.
3. `getResourceId(): string` — return a stable identifier for ACL resource
   checks against the user's own profile (typically `'user'`).
4. `getOwnerId(): mixed` — return the user's primary key (`int|string|null`) so
   that `OwnershipAssertion` can compare it against a profile resource's owner.
5. `getDetail(string $name, mixed $default = null): mixed` — must expose at
   minimum `store_id` for store-scoped ownership assertions.
6. `populate(array $data)` / `toArray(): array` — hydrate from, and serialize
   to, a row array (satisfies `RowPrototypeInterface`).
7. `withId(int|string|null $id): static` — return a copy with a new identity,
   used when persisting a user created without an id.

---

## Checklist

```
□ Concrete user class implements Webware\Core\UserInterface
□ GuestUser::getRoleId() returns UserInterface::GUEST_ROLE
□ User::getRoleId() returns the user's primary role string
□ getOwnerId() returns the user's PK (not store_id — that comes via getDetail('store_id'))
□ getDetail('store_id') returns an int for store-scoped ownership assertions
□ ACL implementations type-hint Webware\Core\AclInterface (not the old per-package interface)
```
