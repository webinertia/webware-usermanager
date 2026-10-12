<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\QueryHandler\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\UserManager\QueryHandler\AuthenticateUserHandler;
use Webware\UserManager\QueryHandler\Container\AuthenticateUserHandlerFactory;
use Webware\UserManager\Repository\UserRepositoryInterface;

#[CoversClass(AuthenticateUserHandlerFactory::class)]
#[CoversMethod(AuthenticateUserHandlerFactory::class, '__invoke')]
final class AuthenticateUserHandlerFactoryTest extends TestCase
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

        self::assertInstanceOf(AuthenticateUserHandler::class, new AuthenticateUserHandlerFactory()($container));
    }
}
