<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Admin\Dashboard\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Admin\Container\Configuration as AdminConfiguration;
use Webware\UserManager\Admin\Dashboard\Container\RegisterWidgetListenerFactory;
use Webware\UserManager\Admin\Dashboard\RegisterWidgetListener;
use Webware\UserManager\Container\Configuration;
use Webware\UserManager\Repository\UserRepositoryInterface;

#[CoversClass(RegisterWidgetListenerFactory::class)]
#[CoversMethod(RegisterWidgetListenerFactory::class, '__invoke')]
final class RegisterWidgetListenerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsListener(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnMap([['config', true]]);
        $container->method('get')
            ->willReturnMap([
                [
                    'config',
                    [
                        AdminConfiguration::CONFIG_KEY => ['admin_route_name_prefix' => 'admin.'],
                        Configuration::CONFIG_KEY      => ['admin_route_name_prefix' => 'user.manager.'],
                    ],
                ],
                [UserRepositoryInterface::class, $this->createStub(UserRepositoryInterface::class)],
            ]);

        self::assertInstanceOf(RegisterWidgetListener::class, (new RegisterWidgetListenerFactory())($container));
    }
}
