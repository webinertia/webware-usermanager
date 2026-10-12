<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Repository;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Repository\UserRepository;
use Webware\UserManager\Repository\UserRepositoryInterface;
use WebwareTest\UserManager\Support\PhpDbAdapterMockTrait;

use function password_hash;

use const PASSWORD_DEFAULT;

#[CoversClass(UserRepository::class)]
#[CoversMethod(UserRepository::class, '__construct')]
#[CoversMethod(UserRepository::class, 'authenticate')]
#[CoversMethod(UserRepository::class, 'checkStatus')]
#[CoversMethod(UserRepository::class, 'findAll')]
#[CoversMethod(UserRepository::class, 'findByEmail')]
#[CoversMethod(UserRepository::class, 'findById')]
#[CoversMethod(UserRepository::class, 'findByVerificationToken')]
#[CoversMethod(UserRepository::class, 'save')]
#[CoversMethod(UserRepository::class, 'insert')]
#[CoversMethod(UserRepository::class, 'update')]
final class UserRepositoryTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function authenticateReturnsNullForWrongPassword(): void
    {
        $hash = password_hash('secret', PASSWORD_DEFAULT);

        $adapter = $this->createAdapter([
            [$this->userRow(
                id          : 1,
                email       : 'jane@example.com',
                passwordHash: $hash,
                active      : 1,
            )],
        ]);

        self::assertNull($this->repository($adapter)->authenticate('jane@example.com', 'wrong'));
    }

    #[Test]
    public function authenticateReturnsNullWhenPasswordIsNull(): void
    {
        $adapter = $this->createAdapter([
            [$this->userRow(
                id          : 1,
                email       : 'jane@example.com',
                passwordHash: password_hash('secret', PASSWORD_DEFAULT),
                active      : 1,
            )],
        ]);

        self::assertNull($this->repository($adapter)->authenticate('jane@example.com'));

        // The row above matches and is active, so any query would have been prepared.
        self::assertSame([], $this->preparedSqlObjects);
    }

    #[Test]
    public function authenticateReturnsNullWhenUserMissing(): void
    {
        $adapter = $this->createAdapter([[]]);

        self::assertNull($this->repository($adapter)->authenticate('missing@example.com', 'secret'));
    }

    #[Test]
    public function authenticateReturnsUserEvenWhenInactive(): void
    {
        $hash = password_hash('secret', PASSWORD_DEFAULT);

        $adapter = $this->createAdapter([
            [$this->userRow(
                id          : 1,
                email       : 'jane@example.com',
                passwordHash: $hash,
                active      : 0,
            )],
        ]);

        self::assertInstanceOf(User::class, $this->repository($adapter)->authenticate('jane@example.com', 'secret'));
    }

    #[Test]
    public function authenticateReturnsUserForValidCredentials(): void
    {
        $hash = password_hash('secret', PASSWORD_DEFAULT);

        $adapter = $this->createAdapter([
            [$this->userRow(
                id          : 1,
                email       : 'jane@example.com',
                passwordHash: $hash,
                active      : 1,
            )],
        ]);

        $user = $this->repository($adapter)->authenticate('jane@example.com', 'secret');

        self::assertInstanceOf(User::class, $user);
        self::assertSame('jane@example.com', $user->email);
    }

    #[Test]
    public function checkStatusReturnsActiveFlag(): void
    {
        $adapter = $this->createAdapter([
            [['active' => 1]],
        ]);

        self::assertTrue($this->repository($adapter)->checkStatus(1));
    }

    #[Test]
    public function checkStatusReturnsFalseWhenInactive(): void
    {
        $adapter = $this->createAdapter([
            [['active' => 0]],
        ]);

        self::assertFalse($this->repository($adapter)->checkStatus(1));
    }

    #[Test]
    public function findAllAppliesOptionalArguments(): void
    {
        $adapter = $this->createAdapter([
            [$this->userRow(
                id   : 1,
                email: 'a@example.com',
            )],
        ]);

        $resultSet = $this->repository($adapter)->findAll(
            selectColumns: ['id', 'email'],
            where        : ['user.active' => 1],
            joins        : [[
                'table'   => 'profile',
                'on'      => 'user.id = profile.userId',
                'columns' => ['profile.displayName'],
                'type'    => Sql\Select::JOIN_INNER,
            ]],
            orderBy      : 'user.id',
            limit        : 10,
            offset       : 5,
        );

        $users = [];
        foreach ($resultSet as $user) {
            $users[] = $user;
        }

        self::assertCount(1, $users);
    }

    #[Test]
    public function findAllReturnsPopulatedResultSet(): void
    {
        $adapter = $this->createAdapter([
            [
                $this->userRow(
                    id   : 1,
                    email: 'a@example.com',
                ),
                $this->userRow(
                    id   : 2,
                    email: 'b@example.com',
                ),
            ],
        ]);

        $resultSet = $this->repository($adapter)->findAll();

        $users = [];
        foreach ($resultSet as $user) {
            $users[] = $user;
        }

        self::assertCount(2, $users);
        self::assertInstanceOf(User::class, $users[0]);
        self::assertSame('a@example.com', $users[0]->email);
        self::assertSame('b@example.com', $users[1]->email);
    }

    #[Test]
    public function findByEmailReturnsNullWhenNotFound(): void
    {
        $adapter = $this->createAdapter([[]]);

        self::assertNull($this->repository($adapter)->findByEmail('missing@example.com'));
    }

    #[Test]
    public function findByEmailReturnsUser(): void
    {
        $adapter = $this->createAdapter([
            [$this->userRow(
                id   : 1,
                email: 'jane@example.com',
            )],
        ]);

        $user = $this->repository($adapter)->findByEmail('jane@example.com');

        self::assertInstanceOf(User::class, $user);
        self::assertSame(1, $user->id);
    }

    #[Test]
    public function findByIdReturnsUser(): void
    {
        $adapter = $this->createAdapter([
            [$this->userRow(
                id   : 42,
                email: 'jane@example.com',
            )],
        ]);

        $user = $this->repository($adapter)->findById(42);

        self::assertInstanceOf(User::class, $user);
        self::assertSame(42, $user->id);
    }

    #[Test]
    public function findByVerificationTokenReturnsUser(): void
    {
        $adapter = $this->createAdapter([
            [$this->userRow(
                id   : 1,
                email: 'jane@example.com',
            )],
        ]);

        $user = $this->repository($adapter)->findByVerificationToken('token');

        self::assertInstanceOf(User::class, $user);
        self::assertSame(1, $user->id);
    }

    #[Test]
    public function insertReturnsGeneratedId(): void
    {
        $adapter = $this->createAdapter([[]], affectedRows: [1], lastGeneratedValue: 99);

        self::assertSame(99, $this->repository($adapter)->insert(['email' => 'jane@example.com']));
    }

    #[Test]
    public function saveInsertsARowAndReturnsGeneratedId(): void
    {
        $adapter = $this->createAdapter([[]], affectedRows: [1], lastGeneratedValue: 99);

        self::assertSame(99, $this->repository($adapter)->save([
            'firstName' => 'Jane',
            'lastName'  => 'Doe',
            'email'     => 'jane@example.com',
        ]));

        /** @var array{table: string, columns: list<string>} $state */
        $state = (array) $this->preparedSqlObjects[0]->getRawState();
        self::assertContains('firstName', $state['columns']);
        self::assertContains('email', $state['columns']);
    }

    #[Test]
    public function saveUpdatesARowCarryingAnId(): void
    {
        $adapter = $this->createAdapter([[]], affectedRows: [1]);

        self::assertSame(1, $this->repository($adapter)->save([
            'id'        => 7,
            'firstName' => 'Jane',
        ]));

        /** @var array{table: string, set: array<string, mixed>} $state */
        $state = (array) $this->preparedSqlObjects[0]->getRawState();
        self::assertArrayHasKey('firstName', $state['set']);
    }

    #[Test]
    public function updateReturnsAffectedRows(): void
    {
        $adapter = $this->createAdapter([[]], affectedRows: [2]);

        self::assertSame(2, $this->repository($adapter)->update(1, ['firstName' => 'Jane']));
    }

    private function repository(AdapterInterface $adapter): UserRepositoryInterface
    {
        return $this->createUserRepository($adapter);
    }

    /**
     * @return array{id: int, roleId: string, firstName: string, lastName: string, email: string, passwordHash: string, active: int, createdAt: string, verificationToken: null, tokenCreatedAt: null, details: null}
     */
    private function userRow(
        int $id = 1,
        string $email = 'jane@example.com',
        string $passwordHash = 'hash',
        int $active = 1,
    ): array {
        return [
            'id'                => $id,
            'roleId'            => 'Member',
            'firstName'         => 'Jane',
            'lastName'          => 'Doe',
            'email'             => $email,
            'passwordHash'      => $passwordHash,
            'active'            => $active,
            'createdAt'         => '2024-01-01 00:00:00',
            'verificationToken' => null,
            'tokenCreatedAt'    => null,
            'details'           => null,
        ];
    }
}
