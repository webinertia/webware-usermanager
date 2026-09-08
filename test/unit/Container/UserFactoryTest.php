<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\ExceptionInterface;
use Psr\Container\ContainerInterface;
use Webware\UserManager\Container\UserFactory;
use Webware\UserManager\Entity\User;

#[CoversClass(UserFactory::class)]
#[CoversMethod(UserFactory::class, '__invoke')]
final class UserFactoryTest extends TestCase
{
    #[Test]
    public function invokeRejectsEmptyData(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [User::class, new User()],
            ]);

        $factory = (new UserFactory())($container);

        $this->expectException(ExceptionInterface::class);

        $factory([]);
    }

    #[Test]
    public function invokeReturnsCallableThatHydratesUser(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [User::class, new User()],
            ]);

        $factory = (new UserFactory())($container);

        $user = $factory(['id' => 1, 'email' => 'jane@example.com']);

        self::assertInstanceOf(User::class, $user);
        self::assertSame(1, $user->id);
        self::assertSame('jane@example.com', $user->email);
    }
}
