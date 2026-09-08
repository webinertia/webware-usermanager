<?php

declare(strict_types=1);

namespace WebwareTestIntegration\UserManager\Repository;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\ResultSet\RowPrototypeResultSet;
use PhpDb\Sql\TableIdentifier;
use PhpDb\TableGateway\TableGateway;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use SensitiveParameter;
use Webware\UserManager\Auth\AuthenticationStatus;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Repository\Schema;
use Webware\UserManager\Repository\UserRepository;
use WebwareTestIntegration\UserManager\Support\AdapterFactory;
use WebwareTestIntegration\UserManager\Support\UserSchema;

use function bin2hex;
use function getenv;
use function password_hash;
use function random_bytes;

use const PASSWORD_DEFAULT;

#[CoversClass(UserRepository::class)]
#[CoversMethod(UserRepository::class, '__construct')]
#[CoversMethod(UserRepository::class, 'insert')]
#[CoversMethod(UserRepository::class, 'findById')]
#[CoversMethod(UserRepository::class, 'findByEmail')]
#[CoversMethod(UserRepository::class, 'findAll')]
#[CoversMethod(UserRepository::class, 'checkStatus')]
#[CoversMethod(UserRepository::class, 'authenticate')]
#[CoversMethod(UserRepository::class, 'update')]
final class UserRepositoryIntegrationTest extends TestCase
{
    private AdapterInterface $adapter;

    private UserRepository $repository;

    #[Test]
    public function authenticateSucceedsWithCorrectPassword(): void
    {
        $password = bin2hex(random_bytes(16));
        $this->repository->insert($this->userData(
            email   : 'jane@example.com',
            password: $password,
        ));

        $result = $this->repository->authenticate('jane@example.com', $password);

        self::assertSame(AuthenticationStatus::Success, $result->status);
        self::assertSame('jane@example.com', $result->user?->email);
    }

    #[Test]
    public function checkStatusReflectsActiveFlag(): void
    {
        $this->repository->insert($this->userData(
            email : 'active@example.com',
            active: 1,
        ));
        $this->repository->insert($this->userData(
            email : 'inactive@example.com',
            active: 0,
        ));

        $activeId   = (int) $this->repository->findByEmail('active@example.com')?->id;
        $inactiveId = (int) $this->repository->findByEmail('inactive@example.com')?->id;

        self::assertTrue($this->repository->checkStatus($activeId));
        self::assertFalse($this->repository->checkStatus($inactiveId));
    }

    #[Test]
    public function findAllReturnsAllUsers(): void
    {
        $this->repository->insert($this->userData(email: 'a@example.com'));
        $this->repository->insert($this->userData(email: 'b@example.com'));

        $emails = [];
        foreach ($this->repository->findAll() as $user) {
            $emails[] = $user->email;
        }

        self::assertSame(['a@example.com', 'b@example.com'], $emails);
    }

    #[Test]
    public function findByEmailReturnsUser(): void
    {
        $this->repository->insert($this->userData(email: 'jane@example.com'));

        self::assertSame('jane@example.com', $this->repository->findByEmail('jane@example.com')?->email);
    }

    #[Test]
    public function insertsAndReadsBackUser(): void
    {
        $id = $this->repository->insert($this->userData(email: 'jane@example.com'));

        $user = $this->repository->findById($id);

        self::assertInstanceOf(User::class, $user);
        self::assertSame('jane@example.com', $user->email);
        self::assertSame('Jane', $user->firstName);
        self::assertTrue($user->active);
    }

    #[Test]
    public function updateChangesStoredUser(): void
    {
        $id = $this->repository->insert($this->userData(email: 'jane@example.com'));

        $this->repository->update($id, ['firstName' => 'Janet', 'active' => 0]);

        $user = $this->repository->findById($id);

        self::assertSame('Janet', $user->firstName);
        self::assertFalse($user->active);
    }

    protected function setUp(): void
    {
        if (! getenv(name: 'TESTS_ADAPTER_MYSQL')) {
            self::markTestSkipped('MySQL adapter is not configured.');
        }

        $this->adapter = AdapterFactory::mysql();
        UserSchema::truncate($this->adapter);

        $gateway = new TableGateway(
            table             : new TableIdentifier(Schema::User->value),
            adapter           : $this->adapter,
            resultSetPrototype: new RowPrototypeResultSet(rowPrototype: new User()),
        );

        $this->repository = new UserRepository(
            gateway         : $gateway,
            dispatcher      : $this->createStub(EventDispatcherInterface::class),
            credentialColumn: 'email',
        );
    }

    /**
     * @return array{roleId: string, firstName: string, lastName: string, email: string, passwordHash: string, active: int}
     */
    private function userData(
        string $email,
        #[SensitiveParameter]
        ?string $password = null,
        int $active = 1,
    ): array {
        return [
            'roleId'       => '["member"]',
            'firstName'    => 'Jane',
            'lastName'     => 'Doe',
            'email'        => $email,
            'passwordHash' => password_hash($password ?? bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
            'active'       => $active,
        ];
    }
}
