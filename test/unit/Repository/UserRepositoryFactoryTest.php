<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Repository;

use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\Core\SchemaFactory;
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
        $adapter       = $this->createAdapter([]);
        $schemaFactory = new SchemaFactory();

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                ['config', ['authentication' => ['username' => 'email']]],
                [SchemaFactory::class, $schemaFactory],
                [AdapterInterface::class, $adapter],
                [User::class, new User()],
                [EventDispatcherInterface::class, $this->createStub(EventDispatcherInterface::class)],
            ]);

        self::assertInstanceOf(UserRepository::class, (new UserRepositoryFactory())($container));
    }
}
