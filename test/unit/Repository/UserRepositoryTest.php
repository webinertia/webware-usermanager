<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Repository;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\UserManager\Auth\AuthenticationStatus;
use Webware\UserManager\Command\CreateUserCommand;
use Webware\UserManager\Command\UpdateUserCommand;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Repository\UserRepository;
use Webware\UserManager\Repository\UserRepositoryInterface;
use WebwareTest\UserManager\Support\PhpDbAdapterMockTrait;

use function bin2hex;
use function password_hash;
use function random_bytes;

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
#[CoversMethod(UserRepository::class, 'findRoleIdByName')]
#[CoversMethod(UserRepository::class, 'insert')]
#[CoversMethod(UserRepository::class, 'update')]
final class UserRepositoryTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    private EventDispatcherInterface $dispatcher;

    #[Test]
    public function authenticateReturnsInvalidCredentialsForWrongPassword(): void
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

        $result = $this->repository($adapter)->authenticate('jane@example.com', 'wrong');

        self::assertSame(AuthenticationStatus::InvalidCredentials, $result->status);
        self::assertNull($result->user);
    }

    #[Test]
    public function authenticateReturnsInvalidCredentialsWhenUserMissing(): void
    {
        $adapter = $this->createAdapter([[]]);

        $result = $this->repository($adapter)->authenticate('missing@example.com', 'secret');

        self::assertSame(AuthenticationStatus::InvalidCredentials, $result->status);
        self::assertNull($result->user);
    }

    #[Test]
    public function authenticateReturnsNotActiveForInactiveUser(): void
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

        $result = $this->repository($adapter)->authenticate('jane@example.com', 'secret');

        self::assertSame(AuthenticationStatus::NotActive, $result->status);
        self::assertNull($result->user);
    }

    #[Test]
    public function authenticateReturnsSuccessForValidCredentials(): void
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

        $result = $this->repository($adapter)->authenticate('jane@example.com', 'secret');

        self::assertSame(AuthenticationStatus::Success, $result->status);
        self::assertInstanceOf(User::class, $result->user);
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
                'table'   => 'store',
                'on'      => 'user.storeId = store.id',
                'columns' => ['store.name'],
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
    public function findRoleIdByNameReturnsRoleName(): void
    {
        $adapter = $this->createAdapter([]);

        self::assertSame('Member', $this->repository($adapter)->findRoleIdByName('Member'));
    }

    #[Test]
    public function insertReturnsGeneratedId(): void
    {
        $adapter = $this->createAdapter([[]], affectedRows: [1], lastGeneratedValue: 99);

        self::assertSame(99, $this->repository($adapter)->insert(['email' => 'jane@example.com']));
    }

    #[Test]
    public function saveInsertsNewCommandAndReturnsGeneratedId(): void
    {
        $adapter = $this->createAdapter([[]], affectedRows: [1], lastGeneratedValue: 99);

        $command = new CreateUserCommand(
            firstName        : 'Jane',
            lastName         : 'Doe',
            passwordHash     : bin2hex(random_bytes(16)),
            email            : 'jane@example.com',
            roleId           : ['member'],
            verificationToken: bin2hex(random_bytes(16)),
        );

        self::assertSame(99, $this->repository($adapter)->save($command));
    }

    #[Test]
    public function saveUpdatesCommandWithId(): void
    {
        $adapter = $this->createAdapter([[]], affectedRows: [1]);

        $command = new UpdateUserCommand(
            id       : 7,
            firstName: 'Jane',
            lastName : 'Doe',
            email    : 'jane@example.com',
            roleId   : ['member'],
            active   : true,
        );

        self::assertSame(1, $this->repository($adapter)->save($command));
    }

    #[Test]
    public function updateReturnsAffectedRows(): void
    {
        $adapter = $this->createAdapter([[]], affectedRows: [2]);

        self::assertSame(2, $this->repository($adapter)->update(1, ['firstName' => 'Jane']));
    }

    protected function setUp(): void
    {
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
    }

    private function repository(AdapterInterface $adapter): UserRepositoryInterface
    {
        return $this->createUserRepository($adapter, $this->dispatcher);
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
            'roleId'            => '["member"]',
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
