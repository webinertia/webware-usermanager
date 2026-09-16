<?php

declare(strict_types=1);

namespace Webware\UserManager\Repository;

use Closure;
use Monolog\Level;
use Override;
use PhpDb\Exception\ExceptionInterface;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDb\ResultSet\RowPrototypeResultSetInterface;
use PhpDb\Sql;
use PhpDb\Sql\Exception\ExceptionInterface as SqlException;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\TableGateway\TableGateway;
use Psl\Type;
use Psl\Type\Exception\ExceptionInterface as PslTypeException;
use Psr\EventDispatcher\EventDispatcherInterface;
use SensitiveParameter;
use Webware\Log\Event\LogEvent;
use Webware\Log\LogChannel;
use Webware\MessageBus\Command\CommandInterface;
use Webware\UserManager\Auth\AuthenticationResult;
use Webware\UserManager\Auth\AuthenticationStatus;
use Webware\UserManager\Entity\User;
use Webware\UserManager\UserInterface;

use function is_array;
use function password_verify;

// @mago-expect lint:too-many-methods - accepted: the repository's public surface mirrors UserRepositoryInterface.
// @mago-expect lint:cyclomatic-complexity - accepted: every branch is a load-bearing guard (nullable credential hash, optional SQL clauses); splitting the class would only relocate them.
final class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly TableGateway $gateway,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly string $credentialColumn,
    ) {}

    /**
     * @throws SqlException
     */
    #[Override]
    public function authenticate(
        string $credential,
        #[SensitiveParameter]
        ?string $password = null,
    ): AuthenticationResult {
        $user = $this->findByConfiguredCredential($this->credentialColumn, $credential);

        if (null === $user) {
            $this->dispatcher->dispatch(new LogEvent(LogChannel::Security, Level::Info)->setMessage(
                'Failed login attempt.',
            )
                ->setContext(['credential' => $credential]));
            return new AuthenticationResult(AuthenticationStatus::InvalidCredentials);
        }

        if (! $user->active) {
            $this->dispatcher->dispatch(new LogEvent(LogChannel::Security, Level::Info)->setMessage(
                "Failed login attempt for inactive user: {$user->email}",
            )
                ->setContext(['credential' => $credential]));
            return new AuthenticationResult(AuthenticationStatus::NotActive);
        }

        // The ?? '' keeps a row with no stored hash from type-erroring password_verify();
        // an empty hash simply never matches, so the result is invalid credentials.
        if (! password_verify($password ?? '', $user->passwordHash ?? '')) {
            $this->dispatcher->dispatch(new LogEvent(LogChannel::Security, Level::Info)->setMessage(
                "Failed login attempt for user: {$user->email}",
            )
                ->setContext(['credential' => $credential]));
            return new AuthenticationResult(AuthenticationStatus::InvalidCredentials);
        }

        $this->dispatcher->dispatch(
            new LogEvent(LogChannel::Security, Level::Info)->setMessage(
                "{$user->firstName} {$user->lastName} authenticated successfully.",
            )
                ->setContext(['identity' => $user->email]),
        );

        return new AuthenticationResult(AuthenticationStatus::Success, $user);
    }

    /**
     * @throws SqlException
     */
    #[Override]
    public function checkStatus(int $id): bool
    {
        $sql    = $this->gateway->getSql();
        $select = $sql->select()->columns(['active'])->where(['user.id' => $id])->limit(1);

        /** @var array<string, mixed>|null $row */
        $row = $sql->prepareStatementForSqlObject($select)->execute()->current();
        return (bool) ($row['active'] ?? false);
    }

    /**
     * @param list<string> $selectColumns
     * @param PredicateInterface|Sql\Where|array<string, mixed>|string|Closure|null $where
     * @param list<array{table: string, on: string, columns?: list<string>|string, type?: string}>|null $joins
     * @throws PslTypeException
     * @throws SqlException
     */
    #[Override]
    // @mago-expect lint:excessive-parameter-list - accepted: the parameter list mirrors the SQL select this method builds.
    public function findAll(
        array $selectColumns = [Sql\Select::SQL_STAR],
        PredicateInterface|array|string|Closure|null $where = null,
        ?array $joins = null,
        ?string $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): ResultSetInterface&RowPrototypeResultSetInterface {
        $sql    = $this->gateway->getSql();
        $select = $sql->select();
        $select->columns($selectColumns);
        if (null !== $where) {
            $select->where($where);
        }
        if (null !== $joins) {
            foreach ($joins as $join) {
                $select->join(
                    $join['table'],
                    $join['on'],
                    $join['columns'] ?? Sql\Select::SQL_STAR,
                    $join['type'] ?? Sql\Select::JOIN_INNER,
                );
            }
        }
        if (null !== $orderBy) {
            $select->order($orderBy);
        }
        if (null !== $limit) {
            $select->limit($limit);
        }
        if (null !== $offset) {
            $select->offset($offset);
        }

        $resultSet = $this->gateway->selectWith($select);

        Type\instance_of(RowPrototypeResultSetInterface::class)->assert($resultSet);

        return $resultSet;
    }

    /**
     * @throws SqlException
     */
    #[Override]
    public function findByEmail(string $email): ?UserInterface
    {
        $sql    = $this->gateway->getSql();
        $select = $sql->select()->where(['user.email' => $email])->limit(1);
        return $this->gateway->selectWith($select)->current();
    }

    /**
     * @throws SqlException
     */
    #[Override]
    public function findById(int $id): ?UserInterface
    {
        $sql    = $this->gateway->getSql();
        $select = $sql->select()->where(['user.id' => $id])->limit(1);

        return $this->gateway->selectWith($select)->current();
    }

    /**
     * @throws SqlException
     */
    #[Override]
    public function findByVerificationToken(#[SensitiveParameter] string $token): ?UserInterface
    {
        $sql    = $this->gateway->getSql();
        $select = $sql->select()->where(['user.verificationToken' => $token])->limit(1);

        return $this->gateway->selectWith($select)->current();
    }

    /**
     * @param array<string, mixed> $data
     * @throws SqlException
     */
    #[Override]
    public function insert(array $data): int
    {
        $sql    = $this->gateway->getSql();
        $insert = $sql->insert()->values($data);

        $this->gateway->insertWith($insert);

        return (int) $this->gateway->getLastInsertValue();
    }

    /**
     * @param CommandInterface|array<string, mixed> $commandOrRow
     * @throws ExceptionInterface
     */
    #[Override]
    public function save(CommandInterface|array $commandOrRow): int
    {
        $data = is_array($commandOrRow) ? $commandOrRow : (array) $commandOrRow;

        // NamedCommandTrait exposes a public command name, so the cast carries it into the
        // row; it is not a column and neither insert() nor update() accepts it.
        unset($data['commandName']);

        if (! isset($data['id'])) {
            $this->gateway->insert($data);

            return (int) $this->gateway->getLastInsertValue();
        }
        return $this->gateway->update($data, ['id' => $data['id']]);
    }

    /**
     * @param array<string, mixed> $data
     * @throws SqlException
     */
    #[Override]
    public function update(int $id, array $data): int
    {
        $sql    = $this->gateway->getSql();
        $update = $sql->update()->set($data);
        $update->where(['id' => $id]);

        return $this->gateway->updateWith($update);
    }

    /**
     * @throws SqlException
     */
    private function findByConfiguredCredential(string $column, string $credential): ?User
    {
        $sql    = $this->gateway->getSql();
        $select = $sql->select()->where([$column => $credential])->limit(1);

        return $this->gateway->selectWith($select)->current();
    }
}
