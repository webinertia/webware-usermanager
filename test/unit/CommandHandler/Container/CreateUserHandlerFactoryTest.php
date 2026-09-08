<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\CommandHandler\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\UserManager\CommandHandler\Container\CreateUserHandlerFactory;
use Webware\UserManager\CommandHandler\CreateUserHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

#[CoversClass(CreateUserHandlerFactory::class)]
#[CoversMethod(CreateUserHandlerFactory::class, '__invoke')]
final class CreateUserHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [UserRepositoryInterface::class, $this->createStub(UserRepositoryInterface::class)],
                [EventDispatcherInterface::class, $this->createStub(EventDispatcherInterface::class)],
            ]);

        self::assertInstanceOf(CreateUserHandler::class, (new CreateUserHandlerFactory())($container));
    }
}
