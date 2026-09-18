<?php

declare(strict_types=1);

namespace Webware\UserManager\Repository;

use Closure;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDb\ResultSet\RowPrototypeResultSetInterface;
use PhpDb\Sql;
use PhpDb\Sql\Predicate\PredicateInterface;
use SensitiveParameter;
use Webware\Core\UserInterface;
use Webware\MessageBus\Command\CommandInterface;
use Webware\UserManager\Auth\AuthenticationResult;

interface UserRepositoryInterface
{
    /**
     * Authenticate a user by credential and password.
     *
     * A successful authentication always returns a fully-hydrated User entity,
     * or null if the credential/password pair is not valid.
     */
    public function authenticate(
        string $credential,
        #[SensitiveParameter]
        ?string $password = null,
    ): AuthenticationResult;

    /**
     * Check if a user is active.
     */
    public function checkStatus(int $id): bool;

    /**
     * Return all users, optionally filtered to a specific store.
     *
     * @param list<string> $selectColumns
     * @param PredicateInterface|Sql\Where|array<string, mixed>|string|Closure|null $where
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
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int;

    /**
     * Persist a user row, given either a command carrying the row or the row itself.
     *
     * @param CommandInterface|array<string, mixed> $commandOrRow
     */
    public function save(CommandInterface|array $commandOrRow): int;

    /**
     * Update an existing user row.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): int;
}
