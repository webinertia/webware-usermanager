<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Middleware\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\Middleware\Container\UserListMiddlewareFactory;
use Webware\UserManager\Http\Middleware\UserListMiddleware;

#[CoversClass(UserListMiddlewareFactory::class)]
#[CoversMethod(UserListMiddlewareFactory::class, '__invoke')]
final class UserListMiddlewareFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsMiddleware(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [MessageBusInterface::class, $this->createStub(MessageBusInterface::class)],
            ]);

        self::assertInstanceOf(UserListMiddleware::class, (new UserListMiddlewareFactory())($container));
    }
}
