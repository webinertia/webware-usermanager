<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Repository;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\SchemaFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Repository\UserRepository;
use Webware\UserManager\Repository\UserRepositoryFactory;
use WebwareTest\UserManager\Support\PhpDbAdapterMockTrait;

#[CoversClass(UserRepositoryFactory::class)]
#[CoversMethod(UserRepositoryFactory::class, '__invoke')]
final class UserRepositoryFactoryTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function invokeBuildsRepository(): void
    {
        self::assertInstanceOf(
            UserRepository::class,
            new UserRepositoryFactory()($this->containerWith(['authentication' => ['username' => 'email']])),
        );
    }

    #[Test]
    public function invokeFallsBackToTheShippedCredentialColumnWhenTheKeyIsAbsent(): void
    {
        $repository = new UserRepositoryFactory()($this->containerWith([
            'authentication' => ['post_login_redirect' => '/'],
        ]));

        self::assertSame(
            'email',
            new ReflectionProperty(UserRepository::class, 'credentialColumn')->getValue($repository),
        );
    }

    #[Test]
    public function invokePassesTheConfiguredCredentialColumnToTheRepository(): void
    {
        $repository = new UserRepositoryFactory()($this->containerWith([
            'authentication' => ['username' => 'user_name'],
        ]));

        self::assertSame(
            'user_name',
            new ReflectionProperty(UserRepository::class, 'credentialColumn')->getValue($repository),
        );
    }

    /** @param array<string, mixed> $config */
    private function containerWith(array $config): ContainerInterface
    {
        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap([
                ['config', $config],
                [SchemaFactory::class, new SchemaFactory()],
                [AdapterInterface::class, $this->createAdapter([])],
                [User::class, new User()],
            ]);

        return $container;
    }
}
