<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\QueryHandler\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
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
        $container->method('get')->willReturn($this->createStub(UserRepositoryInterface::class));

        self::assertInstanceOf(AuthenticateUserHandler::class, (new AuthenticateUserHandlerFactory())($container));
    }
}
