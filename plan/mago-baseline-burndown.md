---
goal: Burn down the mago lint and analysis baselines
version: 0.1
date_created: 2026-09-11
owner: Joey Smith
status: 'In Progress'
tags: [mago, baseline, quality, refactor]
---

# Mago baseline burndown

## Context

`lint-baseline.toml` and `analysis-baseline.toml` currently suppress **123 lint** and
**250 analysis** findings. Measured 2026-09-11 on `0.1.x` at `7005072` (after the
Phase 3 MessageBus merge and the composer update):

- `mago lint --ignore-baseline --stats` → 123 findings, 18 distinct codes
- `mago analyze --ignore-baseline --stats` → 250 findings, 40 distinct codes
- Sum of `count` in each baseline equals those totals exactly, and neither run reports
  stale entries — the baselines have **zero slack**, so no finding is hidden beyond the
  ones counted here.

Useful negative result: clearing baseline entries cannot reveal additional findings.
New findings only appear from changed code or a mago/`webware-tools` bump. The work is
therefore purely "fix the 373 known findings", which is why it is split into tranches
with one PR each.

**Current state (2026-09-11, `chore/mago-burndown-2-mechanical` at `5cd36c1`):** lint
suppresses **43** (was 123) and analysis **230** (was 250). Tranche 1 landed as PR #22;
tranche 2 is partially applied on its branch.

The unapproved Psl `unchecked-exceptions` exemption in `mago.toml` is gone: reverted in
PR #23, merged to `0.1.x` as `8edf30b`, then merged into this branch. Nothing suppresses
`unhandled-thrown-type` any more, so all 72 baselined findings of that code are genuinely
outstanding work for tranche 5, and any new throw site must be documented at the source.

## Policy (locked 2026-09-11)

1. **Safe fixes only.** `mago lint --fix` / `mago analyze --fix` without `--unsafe` or
   `--potentially-unsafe`. Unsafe candidates are surfaced with `--dry-run` and are not
   applied without explicit approval.
2. **Never suppress without approval.** No `@mago-expect` / `@mago-ignore` is added
   without a specific approval for that exact line and code.
3. **Surface anything that could change behavior before changing it.** This explicitly
   includes control-flow restructuring (`no-else-clause`), string building
   (`string-style`), operand reordering (`yoda-conditions`), and any fix where
   equivalence is not self-evident.
4. **Fix at the source**, not at call sites.
5. One issue code per commit; run `mago fmt` first so format churn stays out of the diff.
6. Prune dead baseline entries after each batch:
   `mago lint --remove-outdated-baseline-entries` and the `analyze` equivalent.
7. Re-run the full gate after every batch (see Verification below).

## Verification gate (every batch)

- `mago format --check` — clean
- `mago lint` / `mago analyze` — clean with baseline, and the ignored-issue count must
  drop by exactly the number of findings fixed
- `mago guard` — clean
- `php -d zend.assertions=1 vendor/bin/phpunit --testsuite "unit test"` — green
- Coverage — 100% classes / methods / lines (`XDEBUG_MODE=coverage`, `--cache-directory /tmp/phpunit-cache-um`)

Integration tests need MySQL and run in the tooling container
(`docker compose exec -T tooling composer test-integration`); they cover
`Repository\UserRepository` only.

### Coverage gotchas (measured 2026-09-11)

- **The `zend.assertions=1` requirement is gone** (tranche 2). Each `CommandHandler`
  used to assert its command type as the first line of `handle()`; with the default
  `zend.assertions=-1` that line never executed, so a plain `phpunit --coverage-text`
  run reported 5 classes at 50% methods (94.44% / 97.15% / 99.44%) and the flag was
  needed to reach 100%. The handlers now use `Type\instance_of()->assert()`, which is
  an ordinary statement, so coverage is 100% with or without the flag. The flag is
  still passed for the recorded gate because it costs nothing and keeps the command
  stable.
- **`composer test-coverage` has no `--testsuite` filter**, so it runs unit *and*
  integration. On the host the 6 integration tests error out (the DB hostname `mysql`
  only resolves inside the compose network) and the run reports `Errors: 6`. It is a
  container/CI command: CI overrides `TESTS_ADAPTER_MYSQL_HOSTNAME=127.0.0.1` and runs
  a MySQL service.
- **Container runs leave root-owned artifacts in the workspace**, which silently corrupt
  host measurements: `.phpunit.cache/code-coverage/` (root-owned, causes ~95
  `file_put_contents` permission warnings per host coverage run) and `clover.xml`
  (root-owned, mode 644, so host runs cannot overwrite it and stale numbers persist).
  Use `--cache-directory /tmp/phpunit-cache-um` on the host, and do not trust a
  `clover.xml` whose mtime predates the run that supposedly produced it.

## Inventory — lint (123)

| Code | Level | Count | Tranche |
|---|---|---|---|
| `string-style` | help | 30 | 1 |
| `no-else-clause` | help | 21 | Deferred — kept baselined |
| `literal-named-argument` | warning | 11 | 1 |
| `no-fully-qualified-global-class-like` | help | 11 | 1 |
| `yoda-conditions` | help | 11 | 1 |
| `no-redundant-use` | warning | 9 | 1 |
| `excessive-parameter-list` | error | 6 | 4 |
| `ambiguous-constant-access` | help | 4 | 2 |
| `no-isset` | warning | 4 | 2 |
| `prefer-array-spread` | warning | 4 | 2 — **done** |
| `assert-description` | warning | 3 | 2 — **done** (no `assert()` calls remain) |
| `too-many-methods` | error | 3 | 4 |
| `cyclomatic-complexity` | error | 1 | 4 |
| `halstead` | warning | 1 | 4 |
| `inline-variable-return` | warning | 1 | 2 |
| `no-assign-in-condition` | warning | 1 | 2 |
| `no-literal-password` | error | 1 | 2 |
| `no-negated-ternary` | help | 1 | 2 |

## Inventory — analysis (250)

| Code | Level | Count | Tranche |
|---|---|---|---|
| `unhandled-thrown-type` | error | 72 | 5 |
| `imprecise-type` | warning | 29 | 3 |
| `mixed-assignment` | warning | 15 | 3 |
| `missing-override-attribute` | error | 10 | 2 |
| `mixed-argument` | error | 10 | 3 |
| `unsafe-instantiation` | warning | 10 | 3 |
| `possibly-invalid-argument` | error | 9 | 4 |
| `non-existent-property` | error | 8 | 4 |
| `redundant-null-coalesce` | help | 6 | 2 — **blocked upstream** (mailer #14) |
| `less-specific-argument` | error | 5 | 3 |
| `mixed-return-statement` | error | 5 | 3 |
| `redundant-comparison` | help | 5 | 2 |
| `redundant-cast` | help | 4 | 2 — **blocked upstream** (mailer #14) |
| `redundant-condition` | warning | 4 | 2 |
| `redundant-type-comparison` | warning | 4 | 2 |
| `uninitialized-property` | error | 4 | 4 |
| `class-must-be-final` | warning | 3 | 2 |
| `invalid-return-statement` | error | 3 | 4 |
| `invalid-type-cast` | warning | 3 | 4 |
| `mixed-method-access` | error | 3 | 3 |
| `mixed-operand` | error | 3 | 3 |
| `redundant-docblock-type` | warning | 3 | 2 |
| `redundant-logical-operation` | help | 3 | 2 |
| `unreachable-else-clause` | error | 3 | 4 |
| `impossible-condition` | warning | 2 | 4 |
| `invalid-property-assignment-value` | error | 2 | 4 |
| `mixed-array-access` | error | 2 | 3 |
| `nullable-return-statement` | error | 2 | 4 |
| `possible-method-access-on-null` | error | 2 | 4 |
| `possibly-false-argument` | error | 2 | 4 |
| `possibly-null-argument` | error | 2 | 4 |
| `possibly-null-operand` | warning | 2 | 4 |
| `property-type-coercion` | error | 2 | 4 |
| `docblock-parameter-narrowing` | error | 1 | 3 |
| `impossible-nonnull-entry-check` | warning | 1 | 2 |
| `incompatible-parameter-type` | error | 1 | 3 |
| `less-specific-nested-return-statement` | error | 1 | 3 |
| `missing-api-or-internal` | warning | 1 | 2 |
| `missing-property-type` | warning | 1 | 3 |
| `possibly-undefined-string-array-index` | warning | 1 | 4 |
| `unused-parameter` | help | 1 | 3 |

## Tranches

| # | Scope | Findings | Branch | PR | Status |
|---|---|---|---|---|---|
| 1 | Mechanical lint: autofixable style codes | 93 | `chore/mago-burndown-1-lint-mechanical` | #22 merged | 72 fixed; 21 `no-else-clause` deferred (baselined) |
| 2 | Mechanical analysis + remaining lint: `redundant-*`, `missing-override-attribute`, `no-isset`, `ambiguous-constant-access`, `prefer-array-spread`, `assert-description`, and the long tail of size/level reports | 40 | `chore/mago-burndown-2-mechanical` | — | 11 fixed; `prefer-array-spread` (4) and `assert-description` (3) retired; 3 retired by the `with*` null-merge fix; remainder awaiting decisions |
| 3 | Type precision at the source: `imprecise-type`, `mixed-*`, `unsafe-instantiation`, `less-specific-argument`, docblock narrowing | 82 | — | — | Not started |
| 4 | Error-level correctness: `possibly-*`, `non-existent-property`, `uninitialized-property`, `invalid-*`, `unreachable-else-clause`, plus the remaining lint errors | 38 | — | — | Not started |
| 5 | `unhandled-thrown-type` — document `@throws` using the interface the concrete exception implements | 72 | — | — | Not started |

Tranche 3 and 4 will move as findings resolve each other: fixing a type at its source
(retiring `imprecise-type` or a `mixed-*` root) typically retires downstream findings in
the same code family, so each tranche's count is re-measured when its branch opens
rather than assumed from this table.

## Deferred / blocked findings

**`no-else-clause` (21)** - deferred 2026-09-11 by owner decision: left baselined until
each site is worked through individually. Mago ships no safe autofix for this rule, and
its stated intent (guard clauses and early returns) is a control-flow restructure rather
than a mechanical rewrite.

| Site group | Count | Why it is not mechanical |
|---|---|---|
| `Entity/User.php` property hooks | 10 | value-producing `if`/`elseif`/`else` inside `set` hooks - needs either an early `return;` after assignment or extracted normalizer helpers, and extraction adds methods while `too-many-methods` (3) and `cyclomatic-complexity` (1) are still pending in tranche 4 |
| `Command/CreateUserCommand.php` hooks | 7 | same shape as above |
| `Http/Middleware/IdentityMiddleware.php` | 2 | authentication path - dropping `else` splits the identity/guest decision across two terminal `return $handler->handle(...)` calls |
| `Http/Admin/Middleware/ProcessUpdateUserMiddleware.php` | 1 | a straightforward guard clause, grouped with the above for consistency |
| `Admin/Dashboard/RegisterWidgetListener.php` | 1 | `if`/`else` counter inside a `foreach`; becomes `continue` or a ternary |

The 21 entries stay in `lint-baseline.toml` so `mago lint` remains green. When any site
is fixed later, prune its entry with `--remove-outdated-baseline-entries` in the same
commit.

**`prefer-array-spread` (4)** - **done** (2026-09-11). Landed as `refactor: use array spread
instead of array_merge`, after the `with*` null-merge fix supplied the typed local that
the raw spread needed. Equivalence was verified case by case: integer keys are renumbered
in both forms and string keys let the later operand win in both, so precedence is
unchanged at every site. Mago ships no autofix for this rule.

**Potentially-unsafe set** - mago classifies these as `--potentially-unsafe`, and owner
policy forbids that flag: `redundant-logical-operation` (2), `unused-parameter` (1).
`unused-parameter` removal alters a signature. Each site needs a manual decision.
The six `redundant-null-coalesce` findings previously listed here were not a local
problem at all — see the config-contract entry below.

**`redundant-cast` (4) and `redundant-null-coalesce` (6) — blocked upstream**
(`webware/webware-mailer`). All ten sit in `SendVerificationEmailListenerFactory`, and an
eleventh (`impossible-nonnull-entry-check`, 1) shares the cause: a single over-specific
`@var` shape annotation on the `config` service. The annotation claims every leaf is a
present non-null `string`, so the analyzer reports the four `??` fallbacks and the four
`(string)` casts as redundant. The annotation is false — the `config` service is not
validated at runtime, which is exactly why the fallbacks and casts exist.

Correcting the annotation locally retires 11 findings with no behaviour change, but it
treats the symptom. The root cause is that mailer publishes no config contract: both
`getAdapterConfig()` and `getMessageConfig()` declare `@return array<string, mixed>`, and
the section is read from two different paths by two different consumers
(`PhpMailerFactory` reads `config[AdapterInterface::class]` at top level;
`MailerMiddlewareFactory` reads `config[ConfigProvider::class][AdapterInterface::class]`).
Our own keys (`from_email`, `from_name`, `base_url` under bare `$config['user']`;
`verification_email_subject` under `$config[MailerInterface::class]`) belong to no
component's declared contract at all.

Tracked as **webinertia/webware-mailer#14** (typed property-hook config contract on
`AdapterInterface`, immutable `with*()` adapters, config path, `CommandBus` split into
per-type `Command\` + `CommandHandler\`, PSR middleware to `Http\Middleware`). The namespace
and boundary direction is governed by **webinertia/webware-tools#20**, which carries the
constitution amendment and the central guard rules — neither component defines those rules
locally. These entries stay baselined until the mailer work lands; the usermanager-side key
consts/accessor wait on the same contract.

**Structural lint codes** - `excessive-parameter-list` (6), `too-many-methods` (3),
`cyclomatic-complexity` (1), `halstead` (1), `no-literal-password` (1) need refactors or
design decisions (splitting a parameter list changes call sites; splitting a class changes
the public surface), so they belong with tranche 4 rather than a mechanical pass.

## Findings discovered during the burndown

**Fatal in `User::withRoleId()`, false positive in `User::withDetail()`**
(`src/Entity/User.php`). Both call `array_merge()` on a property declared
`array|string|null`, which is why the analyzer reported `possibly-invalid-argument` on
each. Reverting the prototype and exercising both on a default `User` showed the two are
not equivalent:

- `withRoleId()` — genuine `TypeError: array_merge(): Argument #1 must be of type array,
  null given` on `new User()`. Its hook getter is `get => $this->roleId ?? null`, which
  passes `null` through.
- `withDetail()` — not a runtime fault. Its hook getter is `get => $this->details ?? []`,
  which coerces `null` to `[]` before `array_merge()` sees it. The analyzer does not
  narrow through the getter hook, so the finding is defensive-only here.

Fixed with variant B (an `is_array()` guard into a local), chosen over an `(array)` cast so
no cast is introduced on a nullable value. The `withDetail()` guard is retained as a
type-consistent guard against the declared `array|string|null` even though the getter
currently makes the string branch unreachable.

This also unblocks the `prefer-array-spread` rewrite, which becomes safe once the merged
operand is a typed local rather than a nullable property.

## Notes for the analyze tranches

- Consult `vendor/webware/webware-tools/mago-analysis-types.md` before writing or
  correcting any docblock type (shapes, `list<T>`, `int<min,max>`, `class-string<T>`,
  `interface-string<T>`, `key-of<T>`, conditional types, case-sensitivity rules).
- `@throws` targets the exception **interface** where one exists
  (`Psr\Container\ContainerExceptionInterface`, `Mezzio\Template\Exception\ExceptionInterface`);
  keep the concrete class when it implements no interface.
- Bounce `mago analyze --fix` ↔ `mago lint --fix` ↔ `mago fmt`; analyze fixes can emit
  fully-qualified names that the lint rules then normalize.
