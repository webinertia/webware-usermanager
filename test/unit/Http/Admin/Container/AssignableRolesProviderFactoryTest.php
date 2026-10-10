<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Admin\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\Admin\AssignableRolesProvider;
use Webware\UserManager\Http\Admin\Container\AssignableRolesProviderFactory;

#[CoversClass(AssignableRolesProviderFactory::class)]
#[CoversMethod(AssignableRolesProviderFactory::class, '__invoke')]
final class AssignableRolesProviderFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsProvider(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [MessageBusInterface::class, $this->createStub(MessageBusInterface::class)],
            ]);

        self::assertInstanceOf(
            AssignableRolesProvider::class,
            new AssignableRolesProviderFactory()($container),
        );
    }
}
