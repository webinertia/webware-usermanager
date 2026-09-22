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
use Webware\Message\Http\Middleware\NotificationMiddleware;
use Webware\UserManager\Http\Admin\Middleware\ProcessToggleUserActiveMiddleware;
use Webware\UserManager\Http\Admin\Middleware\ProcessUpdateUserMiddleware;
use Webware\UserManager\Http\Admin\RequestHandler\ToggleUserActiveHandler;
use Webware\UserManager\Http\Admin\RequestHandler\UpdateUserHandler;
use Webware\UserManager\RouteProvider;

use function array_search;
use function in_array;
use function is_array;

#[CoversClass(RouteProvider::class)]
#[CoversMethod(RouteProvider::class, '__construct')]
#[CoversMethod(RouteProvider::class, 'registerRoutes')]
final class RouteProviderTest extends TestCase
{
    #[Test]
    public function adminWriteRoutesNotifyCentrallyAfterDispatch(): void
    {
        $middleware = $this->createStub(MiddlewareInterface::class);

        /** @var list<array<class-string>> $prepared */
        $prepared = [];

        $factory = $this->createStub(MiddlewareFactoryInterface::class);
        $factory->method('prepare')
            ->willReturnCallback(
                static function (mixed $pipeline) use (&$prepared, $middleware): MiddlewareInterface {
                    if (is_array($pipeline)) {
                        /** @var array<class-string> $pipeline */
                        $prepared[] = $pipeline;
                    }

                    return $middleware;
                },
            );

        $collector = $this->createStub(RouteCollectorInterface::class);
        $collector->method('get')
            ->willReturnCallback(
                static fn(string $path, MiddlewareInterface $mw, ?string $name = null): Route => new Route(
                    $path,
                    $mw,
                    ['GET'],
                    $name,
                ),
            );
        $collector->method('post')
            ->willReturnCallback(
                static fn(string $path, MiddlewareInterface $mw, ?string $name = null): Route => new Route(
                    $path,
                    $mw,
                    ['POST'],
                    $name,
                ),
            );
        $collector->method('route')
            ->willReturnCallback(
                static fn(
                    string $path,
                    MiddlewareInterface $mw,
                    ?array $methods = null,
                    ?string $name = null,
                ): Route => new Route($path, $mw, $methods ?? [], $name),
            );

        $provider = new RouteProvider(
            routeSegment     : 'user.manager',
            adminRouteSegment: 'admin/user.manager',
            routeNames       : $this->fixtureRouteNames(),
        );

        $provider->registerRoutes($collector, $factory);

        self::assertSame(
            NotificationMiddleware::class,
            $this->middlewareAfter(ProcessUpdateUserMiddleware::class, UpdateUserHandler::class, $prepared),
        );
        self::assertSame(
            NotificationMiddleware::class,
            $this->middlewareAfter(
                ProcessToggleUserActiveMiddleware::class,
                ToggleUserActiveHandler::class,
                $prepared,
            ),
        );
    }

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
            routeSegment     : 'user.manager',
            adminRouteSegment: 'admin/user.manager',
            routeNames       : $this->fixtureRouteNames(),
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

    /**
     * The route names the provider is now handed, rather than composing them itself.
     * Composition is the factory's job and is verified in RouteProviderFactoryTest.
     *
     * @return array<string, non-empty-string>
     */
    private function fixtureRouteNames(): array
    {
        return [
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
        ];
    }

    /**
     * Returns the middleware immediately following $process in the pipeline that
     * ends with $terminal, or null when that pipeline cannot be located.
     *
     * @param list<array<class-string>> $prepared
     */
    private function middlewareAfter(string $process, string $terminal, array $prepared): ?string
    {
        foreach ($prepared as $pipeline) {
            if (! in_array($terminal, $pipeline, strict: true)) {
                continue;
            }

            $index = array_search($process, $pipeline, strict: true);

            if (false === $index) {
                return null;
            }

            return $pipeline[$index + 1] ?? null;
        }

        return null;
    }
}
