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
use Webware\Htmx\Http\Middleware\DisableBodyMiddleware;
use Webware\Message\Http\Middleware\NotificationMiddleware;
use Webware\UserManager\Http\Admin\Middleware\ProcessCreateUserMiddleware;
use Webware\UserManager\Http\Admin\Middleware\ProcessToggleUserActiveMiddleware;
use Webware\UserManager\Http\Admin\Middleware\ProcessUpdateUserMiddleware;
use Webware\UserManager\Http\Admin\Middleware\UpdateUserModalMiddleware;
use Webware\UserManager\Http\Admin\RequestHandler\CreateUserHandler;
use Webware\UserManager\Http\Admin\RequestHandler\ToggleUserActiveHandler;
use Webware\UserManager\Http\Admin\RequestHandler\UpdateUserHandler;
use Webware\UserManager\Http\Admin\RequestHandler\UpdateUserModalHandler;
use Webware\UserManager\Http\Middleware\UserListMiddleware;
use Webware\UserManager\Http\RequestHandler\UserListHandler;
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
            routeSegment        : 'user',
            routeNamePrefix     : 'user.',
            adminRouteSegment   : 'admin/user',
            adminRouteNamePrefix: 'admin.user.',
        );

        $provider->registerRoutes($collector, $factory);

        // UserListMiddleware supplies the view model the list handlers render, and
        // has to run after the Process* middleware so the list it fetches includes the
        // command that has just run.
        self::assertContains(
            [UserListMiddleware::class, UserListHandler::class],
            $prepared,
        );
        // UpdateUserModalMiddleware supplies the view model UpdateUserModalHandler renders.
        self::assertSame(
            UpdateUserModalMiddleware::class,
            $this->middlewareAfter(
                DisableBodyMiddleware::class,
                UpdateUserModalHandler::class,
                $prepared,
            ),
        );
        self::assertSame(
            UserListMiddleware::class,
            $this->middlewareAfter(ProcessUpdateUserMiddleware::class, UpdateUserHandler::class, $prepared),
        );
        self::assertSame(
            NotificationMiddleware::class,
            $this->middlewareAfter(UserListMiddleware::class, UpdateUserHandler::class, $prepared),
        );
        self::assertSame(
            UserListMiddleware::class,
            $this->middlewareAfter(
                ProcessCreateUserMiddleware::class,
                CreateUserHandler::class,
                $prepared,
            ),
        );
        self::assertSame(
            NotificationMiddleware::class,
            $this->middlewareAfter(
                UserListMiddleware::class,
                CreateUserHandler::class,
                $prepared,
            ),
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
        $collector->expects($this->exactly(7))
            ->method('get')
            ->willReturnCallback(
                static function (string $path, MiddlewareInterface $mw, ?string $name = null) use (&$gets): Route {
                    $gets[] = [$path, $name];

                    return new Route($path, $mw, ['GET'], $name);
                },
            );
        $collector->expects($this->exactly(6))
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
            routeSegment        : 'user',
            routeNamePrefix     : 'user.',
            adminRouteSegment   : 'admin/user',
            adminRouteNamePrefix: 'admin.user.',
        );

        $provider->registerRoutes($collector, $factory);

        self::assertSame(
            [
                ['/user/login',                'user.session.read'],
                ['/user/register',             'user.register.read'],
                ['/user/verify.email/{token}', 'user.verify.email.read'],
                ['/user/set.password/{token}', 'user.set.password.read'],
                ['/user/resend.verification',  'user.resend.verification.read'],
                ['/user/logout',               'user.logout.read'],
                ['/admin/user',                'admin.user'],
            ],
            $gets,
        );

        self::assertSame(
            [
                ['/user/login',                 'user.session.create'],
                ['/user/register',              'user.register.create'],
                ['/user/set.password/{token}',  'user.set.password.create'],
                ['/user/resend.verification',   'user.resend.verification.create'],
                ['/admin/user/create',          'admin.user.create'],
                ['/admin/user/{id:\d+}/toggle', 'admin.user.toggle.update'],
            ],
            $posts,
        );

        self::assertSame(
            [
                ['/admin/user/create/modal', ['GET'], 'admin.user.create.modal'],
                ['/admin/user/update/{id:\d+}', ['PATCH'], 'admin.user.update'],
                ['/admin/user/update/{id:\d+}', ['GET'], 'admin.user.update.modal'],
            ],
            $routes,
        );
    }

    #[Test]
    public function tagsLoginRegisterAndLogoutForTheUserNavigation(): void
    {
        $middleware = $this->createStub(MiddlewareInterface::class);

        $factory = $this->createStub(MiddlewareFactoryInterface::class);
        $factory->method('prepare')->willReturn($middleware);

        /** @var array<string, Route> $byName */
        $byName = [];

        $register = static function (
            string $path,
            MiddlewareInterface $mw,
            ?string $name = null,
            array $methods = [],
        ) use (&$byName): Route {
            return $byName[(string) $name] = new Route($path, $mw, $methods, $name);
        };

        $collector = $this->createStub(RouteCollectorInterface::class);
        $collector->method('get')
            ->willReturnCallback(
                static fn(string $path, MiddlewareInterface $mw, ?string $name = null): Route => $register(
                    $path,
                    $mw,
                    $name,
                    ['GET'],
                ),
            );
        $collector->method('post')
            ->willReturnCallback(
                static fn(string $path, MiddlewareInterface $mw, ?string $name = null): Route => $register(
                    $path,
                    $mw,
                    $name,
                    ['POST'],
                ),
            );
        $collector->method('route')
            ->willReturnCallback(
                static fn(
                    string $path,
                    MiddlewareInterface $mw,
                    ?array $methods = null,
                    ?string $name = null,
                ): Route => $register(
                    $path,
                    $mw,
                    $name,
                    $methods ?? [],
                ),
            );

        new RouteProvider(
            routeSegment        : 'user',
            routeNamePrefix     : 'user.',
            adminRouteSegment   : 'admin/user',
            adminRouteNamePrefix: 'admin.user.',
        )->registerRoutes($collector, $factory);

        self::assertSame(
            [
                'navigation' => 'user',
                'label'      => 'Login',
                'icon'       => 'bi-box-arrow-in-right',
                'parent'     => null,
                'order'      => 10,
            ],
            $byName['user.session.read']->getOptions(),
        );
        self::assertSame(
            [
                'navigation' => 'user',
                'label'      => 'Register',
                'icon'       => 'bi-person-plus',
                'parent'     => null,
                'order'      => 20,
            ],
            $byName['user.register.read']->getOptions(),
        );
        self::assertSame(
            [
                'navigation' => 'user',
                'label'      => 'Logout',
                'icon'       => 'bi-box-arrow-right',
                'parent'     => null,
                'order'      => 10,
            ],
            $byName['user.logout.read']->getOptions(),
        );
        self::assertSame([], $byName['user.session.create']->getOptions());
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
