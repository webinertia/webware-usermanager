<?php

declare(strict_types=1);

namespace WebwareTest\UserManager;

use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Router\Route;
use Mezzio\Router\RouteCollectorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Webware\UserManager\RouteProvider;

#[CoversClass(RouteProvider::class)]
#[CoversMethod(RouteProvider::class, '__construct')]
#[CoversMethod(RouteProvider::class, 'registerRoutes')]
final class RouteProviderTest extends TestCase
{
    #[Test]
    public function registersAllPublicAndAdminRoutes(): void
    {
        $middleware = $this->createStub(MiddlewareInterface::class);

        $factory = $this->createStub(MiddlewareFactoryInterface::class);
        $factory->method('prepare')->willReturn($middleware);

        $gets   = [];
        $posts  = [];
        $routes = [];

        $collector = $this->createMock(RouteCollectorInterface::class);
        $collector->expects($this->exactly(6))
            ->method('get')
            ->willReturnCallback(
                static function (string $path, MiddlewareInterface $mw, ?string $name = null) use (&$gets): Route {
                    $gets[] = [$path, $name];

                    return new Route($path, $mw, ['GET'], $name);
                },
            );
        $collector->expects($this->exactly(4))
            ->method('post')
            ->willReturnCallback(
                static function (string $path, MiddlewareInterface $mw, ?string $name = null) use (&$posts): Route {
                    $posts[] = [$path, $name];

                    return new Route($path, $mw, ['POST'], $name);
                },
            );
        $collector->expects($this->exactly(3))
            ->method('route')
            ->willReturnCallback(
                static function (
                    string $path,
                    MiddlewareInterface $mw,
                    ?array $methods = null,
                    ?string $name = null,
                ) use (&$routes): Route {
                    $routes[] = [$path, $methods, $name];

                    return new Route($path, $mw, $methods, $name);
                },
            );

        $provider = new RouteProvider(
            routeSegment        : 'user.manager',
            routeNamePrefix     : 'user.manager.',
            adminRouteSegment   : 'admin/user.manager',
            adminRouteNamePrefix: 'admin.user.manager.',
        );

        $provider->registerRoutes($collector, $factory);

        self::assertSame(
            [
                ['/user.manager/login',                'user.manager.session.read'],
                ['/user.manager/register',             'user.manager.register.read'],
                ['/user.manager/verify.email/{token}', 'user.manager.verify.email.read'],
                ['/user.manager/resend.verification',  'user.manager.resend.verification.read'],
                ['/user.manager/logout',               'user.manager.logout.read'],
                ['/admin/user.manager',                'admin.user.manager'],
            ],
            $gets,
        );

        self::assertSame(
            [
                ['/user.manager/login',                 'user.manager.session.create'],
                ['/user.manager/register',              'user.manager.register.create'],
                ['/user.manager/resend.verification',   'user.manager.resend.verification.create'],
                ['/admin/user.manager/{id:\d+}/toggle', 'admin.user.manager.toggle.update'],
            ],
            $posts,
        );

        self::assertSame(
            [
                ['/admin/user.manager/create', ['GET', 'POST'], 'admin.user.manager.create'],
                ['/admin/user.manager/update/{id:\d+}', ['PATCH'], 'admin.user.manager.update'],
                ['/admin/user.manager/update/{id:\d+}', ['GET'], 'admin.user.manager.update.modal'],
            ],
            $routes,
        );
    }
}
