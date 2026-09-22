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
        $provider = (new RouteProviderFactory())($this->container());

        self::assertSame('user.manager', $this->property($provider, 'routeSegment'));
        self::assertSame('admin/user.manager', $this->property($provider, 'adminRouteSegment'));
        self::assertSame(
            [
                'session.read'               => 'user.manager.session.read',
                'session.create'             => 'user.manager.session.create',
                'register.read'              => 'user.manager.register.read',
                'register.create'            => 'user.manager.register.create',
                'verify.email.read'          => 'user.manager.verify.email.read',
                'resend.verification.read'   => 'user.manager.resend.verification.read',
                'resend.verification.create' => 'user.manager.resend.verification.create',
                'logout.read'                => 'user.manager.logout.read',
                'admin.index'                => 'admin.user.manager',
                'admin.create'               => 'admin.user.manager.create',
                'admin.update'               => 'admin.user.manager.update',
                'admin.update.modal'         => 'admin.user.manager.update.modal',
                'admin.toggle.update'        => 'admin.user.manager.toggle.update',
            ],
            $this->property($provider, 'routeNames'),
        );
    }

    #[Test]
    public function invokeLetsAConsumerOverrideIndividualRouteNames(): void
    {
        $provider = (new RouteProviderFactory())($this->container(['session.read' => 'shop.login']));

        /** @var array<string, non-empty-string> $names */
        $names = $this->property($provider, 'routeNames');

        // The overridden name moves, and the names it did not override do not.
        self::assertSame('shop.login', $names['session.read']);
        self::assertSame('user.manager.logout.read', $names['logout.read']);
    }

    /**
     * @param array<string, non-empty-string> $overrides
     */
    private function container(array $overrides = []): ContainerInterface
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
                            'routes'                  => $overrides,
                        ],
                    ],
                ],
            ]);

        return $container;
    }

    private function property(RouteProvider $provider, string $name): mixed
    {
        return new ReflectionProperty(RouteProvider::class, $name)->getValue($provider);
    }
}
