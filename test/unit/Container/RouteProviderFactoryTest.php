<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use Webware\Admin\Container\Configuration as AdminConfiguration;
use Webware\UserManager\Container\Configuration;
use Webware\UserManager\Container\RouteProviderFactory;
use Webware\UserManager\RouteProvider;

#[CoversClass(RouteProviderFactory::class)]
#[CoversMethod(RouteProviderFactory::class, '__invoke')]
final class RouteProviderFactoryTest extends TestCase
{
    #[Test]
    public function invokeCombinesAdminAndModuleSegments(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnMap([['config', true]]);
        $container->method('get')
            ->willReturnMap([
                [
                    'config',
                    [
                        AdminConfiguration::CONFIG_KEY => [
                            'admin_route_segment'     => 'admin',
                            'admin_route_name_prefix' => 'admin.',
                        ],
                        Configuration::CONFIG_KEY      => [
                            'route_segment'           => 'user.manager',
                            'route_name_prefix'       => 'user.manager.',
                            'admin_route_segment'     => 'user.manager',
                            'admin_route_name_prefix' => 'user.manager.',
                        ],
                    ],
                ],
            ]);

        $provider = (new RouteProviderFactory())($container);

        self::assertSame('user.manager', $this->property($provider, 'routeSegment'));
        self::assertSame('user.manager.', $this->property($provider, 'routeNamePrefix'));
        self::assertSame('admin/user.manager', $this->property($provider, 'adminRouteSegment'));
        self::assertSame('admin.user.manager.', $this->property($provider, 'adminRouteNamePrefix'));
    }

    private function property(RouteProvider $provider, string $name): string
    {
        return new ReflectionProperty(RouteProvider::class, $name)->getValue($provider);
    }
}
