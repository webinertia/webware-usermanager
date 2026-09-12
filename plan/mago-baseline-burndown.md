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

## Inventory — lint (123)

| Code | Level | Count | Tranche |
|---|---|---|---|
| `string-style` | help | 30 | 1 |
| `no-else-clause` | help | 21 | 1 (approval required) |
| `literal-named-argument` | warning | 11 | 1 |
| `no-fully-qualified-global-class-like` | help | 11 | 1 |
| `yoda-conditions` | help | 11 | 1 |
| `no-redundant-use` | warning | 9 | 1 |
| `excessive-parameter-list` | error | 6 | 4 |
| `ambiguous-constant-access` | help | 4 | 2 |
| `no-isset` | warning | 4 | 2 |
| `prefer-array-spread` | warning | 4 | 2 |
| `assert-description` | warning | 3 | 2 |
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
| `redundant-null-coalesce` | help | 6 | 2 |
| `less-specific-argument` | error | 5 | 3 |
| `mixed-return-statement` | error | 5 | 3 |
| `redundant-comparison` | help | 5 | 2 |
| `redundant-cast` | help | 4 | 2 |
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
| 1 | Mechanical lint: autofixable style codes | 93 | `chore/mago-burndown-1-lint-mechanical` | — | In progress |
| 2 | Mechanical analysis + remaining lint: `redundant-*`, `missing-override-attribute`, `no-isset`, `ambiguous-constant-access`, `prefer-array-spread`, `assert-description`, and the long tail of size/level reports | 40 | — | — | Not started |
| 3 | Type precision at the source: `imprecise-type`, `mixed-*`, `unsafe-instantiation`, `less-specific-argument`, docblock narrowing | 82 | — | — | Not started |
| 4 | Error-level correctness: `possibly-*`, `non-existent-property`, `uninitialized-property`, `invalid-*`, `unreachable-else-clause`, plus the remaining lint errors | 38 | — | — | Not started |
| 5 | `unhandled-thrown-type` — document `@throws` using the interface the concrete exception implements | 72 | — | — | Not started |

Tranche 3 and 4 will move as findings resolve each other: fixing a type at its source
(retiring `imprecise-type` or a `mixed-*` root) typically retires downstream findings in
the same code family, so each tranche's count is re-measured when its branch opens
rather than assumed from this table.

## Notes for the analyze tranches

- Consult `vendor/webware/webware-tools/mago-analysis-types.md` before writing or
  correcting any docblock type (shapes, `list<T>`, `int<min,max>`, `class-string<T>`,
  `interface-string<T>`, `key-of<T>`, conditional types, case-sensitivity rules).
- `@throws` targets the exception **interface** where one exists
  (`Psr\Container\ContainerExceptionInterface`, `Mezzio\Template\Exception\ExceptionInterface`);
  keep the concrete class when it implements no interface.
- Bounce `mago analyze --fix` ↔ `mago lint --fix` ↔ `mago fmt`; analyze fixes can emit
  fully-qualified names that the lint rules then normalize.
