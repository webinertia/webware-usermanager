<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\CommandHandler\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\UserManager\CommandHandler\Container\UpdateUserHandlerFactory;
use Webware\UserManager\CommandHandler\UpdateUserHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

#[CoversClass(UpdateUserHandlerFactory::class)]
#[CoversMethod(UpdateUserHandlerFactory::class, '__invoke')]
final class UpdateUserHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [UserRepositoryInterface::class, $this->createStub(UserRepositoryInterface::class)],
            ]);

        self::assertInstanceOf(UpdateUserHandler::class, (new UpdateUserHandlerFactory())($container));
    }
}
