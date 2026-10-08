<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Admin\Middleware\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\Admin\AssignableRolesProvider;
use Webware\UserManager\Http\Admin\Middleware\Container\UpdateUserModalMiddlewareFactory;
use Webware\UserManager\Http\Admin\Middleware\UpdateUserModalMiddleware;

#[CoversClass(UpdateUserModalMiddlewareFactory::class)]
#[CoversMethod(UpdateUserModalMiddlewareFactory::class, '__invoke')]
final class UpdateUserModalMiddlewareFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsMiddleware(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [MessageBusInterface::class, $this->createStub(MessageBusInterface::class)],
                [
                    AssignableRolesProvider::class,
                    new AssignableRolesProvider($this->createStub(MessageBusInterface::class)),
                ],
            ]);

        self::assertInstanceOf(
            UpdateUserModalMiddleware::class,
            (new UpdateUserModalMiddlewareFactory())($container),
        );
    }
}
