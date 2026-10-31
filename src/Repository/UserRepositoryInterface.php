<?php

declare(strict_types=1);

namespace Webware\UserManager\Repository;

use Closure;
use Mezzio\Authentication\UserRepositoryInterface as MezzioUserRepositoryInterface;
use Override;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDb\ResultSet\RowPrototypeResultSetInterface;
use PhpDb\Sql;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\SqlInterface;
use SensitiveParameter;
use Webware\Core\UserInterface;
use Webware\UserManager\Entity\User;

/**
 * The argument shapes phpdb accepts, mirroring its own unions so they track upstream instead of
 * being restated at each call site.
 *
 * @type WhereArg array<array-key, ArgumentInterface|ExpressionInterface|PredicateInterface|SqlInterface|list<bool|float|int|null|string>|null|scalar>
 * @type ColumnValue ExpressionInterface|Select|null|scalar
 */
interface UserRepositoryInterface extends MezzioUserRepositoryInterface
{
    /**
     * Authenticate a user by credential and password.
     *
     * Implements the Mezzio authentication repository contract: the hydrated
     * user, or null when the pair does not match. The inherited return type is
     * narrowed to the entity this component hands out.
     *
     * A null password returns null without querying. Mezzio's contract also
     * permits a credential-only lookup for token-based credentials, which this
     * implementation does not provide: the verification token column is for
     * email activation, not for authentication, so there is nothing to match
     * against. A host migrating a token-based Mezzio repository must keep its
     * own implementation.
     *
     * An empty password is never a valid credential, and is rejected before the
     * lookup as well.
     *
     * Account state is policy rather than authentication, so an inactive account
     * still authenticates and the caller decides whether it may proceed.
     */
    #[Override]
    public function authenticate(
        string $credential,
        #[SensitiveParameter]
        ?string $password = null,
    ): (UserInterface&User)|null;

    /**
     * Check if a user is active.
     */
    public function checkStatus(int $id): bool;

    /**
     * Return all users. Narrowing is the caller's: pass $where, $joins and the
     * remaining optional arguments to filter the select.
     *
     * @param list<string> $selectColumns
     * @param PredicateInterface|Sql\Where|WhereArg|string|Closure|null $where
     * @param list<array{table: string, on: string, columns?: list<string>|string, type?: string}>|null $joins
     */
    // @mago-expect lint:excessive-parameter-list - accepted: the parameter list mirrors the SQL select findAll() builds.
    public function findAll(
        array $selectColumns = [Sql\Select::SQL_STAR],
        PredicateInterface|array|string|Closure|null $where = null,
        ?array $joins = null,
        ?string $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): ResultSetInterface&RowPrototypeResultSetInterface;

    /**
     * Find a user by their email address, or null if not found.
     */
    public function findByEmail(string $email): ?UserInterface;

    /**
     * Find a user by their primary key, or null if not found.
     */
    public function findById(int $id): ?UserInterface;

    /**
     * Find a user by their verification token, or null if not found.
     */
    public function findByVerificationToken(#[SensitiveParameter] string $token): ?UserInterface;

    /**
     * Persist a new user row and return the generated id.
     *
     * @param array<string, ColumnValue> $data
     */
    public function insert(array $data): int;

    /**
     * Persist a user row and return the affected row count.
     *
     * @param array<string, mixed> $data
     */
    public function save(array $data): int;

    /**
     * Update an existing user row.
     *
     * @param array<string, ColumnValue> $data
     */
    public function update(int $id, array $data): int;
}
