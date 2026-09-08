<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Middleware\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Core\UserInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Http\Middleware\Container\IdentityMiddlewareFactory;
use Webware\UserManager\Http\Middleware\IdentityMiddleware;

#[CoversClass(IdentityMiddlewareFactory::class)]
#[CoversMethod(IdentityMiddlewareFactory::class, '__invoke')]
final class IdentityMiddlewareFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsMiddleware(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [MessageBusInterface::class, $this->createStub(MessageBusInterface::class)],
                [UserInterface::class, static fn(): User => new User()],
            ]);

        self::assertInstanceOf(IdentityMiddleware::class, (new IdentityMiddlewareFactory())($container));
    }
}
