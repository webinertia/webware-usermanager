<?php

declare(strict_types=1);

namespace Webware\UserManager\Repository;

use Closure;
use PhpDb\ResultSet\RowPrototypeResultSetInterface;
use PhpDb\Sql;
use PhpDb\Sql\Predicate\PredicateInterface;
use SensitiveParameter;
use Webware\Core\UserInterface;
use Webware\MessageBus\Command\CommandInterface;

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
    ): \Webware\UserManager\Auth\AuthenticationResult;

    /**
     * Check if a user is active.
     */
    public function checkStatus(int $id): bool;

    /**
     * Return all users, optionally filtered to a specific store.
     */
    public function findAll(
        array $selectColumns = [Sql\Select::SQL_STAR],
        PredicateInterface|array|string|Closure|null $where = null,
        ?array $joins = null,
        ?string $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): ?RowPrototypeResultSetInterface;

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
     * Return the role identifier string for the given role name.
     */
    public function findRoleIdByName(string $roleName): string;

    /**
     * Persist a new user row and return the generated id.
     *
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int;

    public function save(CommandInterface $command): int;

    /**
     * Update an existing user row.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): int;
}
