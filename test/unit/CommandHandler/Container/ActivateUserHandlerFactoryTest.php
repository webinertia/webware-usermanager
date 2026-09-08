<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\CommandHandler\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\UserManager\CommandHandler\ActivateUserHandler;
use Webware\UserManager\CommandHandler\Container\ActivateUserHandlerFactory;
use Webware\UserManager\Repository\UserRepositoryInterface;

#[CoversClass(ActivateUserHandlerFactory::class)]
#[CoversMethod(ActivateUserHandlerFactory::class, '__invoke')]
final class ActivateUserHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturn($this->createStub(UserRepositoryInterface::class));

        static::assertInstanceOf(ActivateUserHandler::class, (new ActivateUserHandlerFactory())($container));
    }
}
