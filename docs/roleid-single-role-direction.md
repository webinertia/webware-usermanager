# `roleId` — Single-Role Direction

> Recorded 2026-09-17. **Direction only — not implemented.** Every other document in this
> folder that describes `roleId` as an array predates this decision.

---

## The problem with the current approach

Approaching `roleId` as an array put the array at the centre and treated the single role as a
derived value. In order, that produced:

- a nullable `array|string|null` property, because `null` had to be representable
- an `array_shift` / index-0 collapsing hook, because only one element was ever legitimate
- a `withRoleId()` that merged arrays and then had to be changed to replace them
- a `getRoleId()` that could not satisfy Laminas' `string` contract while returning the raw property
- `Webware\Acl\Role\SingleRoleUserProxy`, which exists only to present one role to Mezzio's
  `iterable` contract

The direction is inverted: a string, wrapped once, at the contract boundary.

## The direction

- `$roleId` is typed hard to **`string`**. That is all it is.
- `getRoles()` **wraps that string in an array** — nothing more.
- The `roleId` DB column becomes **`varchar(50)`**.
- `SingleRoleUserProxy` is **never needed** — build to the string.
- `getRoles()` is only reached at **boundaries we never touch**, e.g. Laminas permissions
  calling `getRoles()`.

## What changes

| Where | Now | Direction |
|---|---|---|
| `Entity\User::$roleId` | `array\|string` + `array_shift` index-0 hook | `string` |
| `Entity\User::getRoles()` | normalises the property into a list | `return [$this->roleId];` |
| `Entity\User::withRoleId()` | wraps and merges arrays | takes and stores a plain string |
| `Console\UserSchema` — `roleId` | `Json`, `nullable: false` | `Varchar(50)` |
| acl `Role/SingleRoleUserProxy.php` | presents one role as a Mezzio `iterable` | deleted |
| acl `Role/UserRoleIterator.php` | iterates the role list | unnecessary |

Unchanged by this direction:

- `Entity\User::getRoleId(): string`
- ACL role names — proper nouns: `'Guest'`, `'Member'`, `'Administrator'`, `'Developer'`
- `getIdentity()` — the email address, or `'Guest'` for a guest principal
- the authorization boundary; Laminas ACL resolves roles through `getRoleId()`

## Precedent

`acl_role.roleId` is already `VARCHAR(50)` holding a single role name
(see `roleid-audit-results.md`). `user.roleId` is the outlier, not the model.

## Existing data

`roleid-audit-results.md` records live rows holding more than one role, e.g.
`["Member","Warehouse"]`. Moving the column to `varchar(50)` requires deciding what happens to
those rows — the direction assumes exactly one role per user.
