<?php

declare(strict_types=1);

namespace Webware\UserManager\Container;

use Psl\Type;
use Psl\Type\Exception\ExceptionInterface as PslTypeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Webware\Admin\Container\Configuration as AdminConfiguration;
use Webware\Core\UserInterface;
use Webware\UserManager\ConfigProvider;
use Webware\UserManager\RouteProvider;

use function rtrim;

/**
 * @import-type RouteNames from ConfigProvider
 */
final readonly class RouteProviderFactory
{
    /**
     * Consumer overrides for individual route names, so a host that already owns a
     * route name can avoid a collision without forking the component.
     *
     * @throws ContainerExceptionInterface
     * @throws PslTypeException
     *
     * @return array<string, non-empty-string>
     */
    private function overrides(ContainerInterface $container): array
    {
        /** @var array<string, mixed> $config */
        $config = $container->get('config');

        /** @var array<string, mixed> $section */
        $section = $config[UserInterface::class] ?? [];

        return Type\dict(Type\non_empty_string(), Type\non_empty_string())->coerce($section['routes'] ?? []);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws PslTypeException
     */
    public function __invoke(ContainerInterface $container): RouteProvider
    {
        $routeSegment    = Configuration::getRouteSegment($container, self::class);
        $routeNamePrefix = Configuration::getRouteNamePrefix($container, self::class);

        $adminBaseRouteSegment      = AdminConfiguration::getAdminRouteSegment($container, self::class);
        $moduleAdminRouteSegment    = Configuration::getAdminRouteSegment($container, self::class);
        $adminBaseRouteNamePrefix   = AdminConfiguration::getAdminRouteNamePrefix($container, self::class);
        $moduleAdminRouteNamePrefix = Configuration::getAdminRouteNamePrefix($container, self::class);

        $adminRouteSegment    = "{$adminBaseRouteSegment}/{$moduleAdminRouteSegment}";
        $adminRouteNamePrefix = $adminBaseRouteNamePrefix . $moduleAdminRouteNamePrefix;

        // Composed once, from resolved config. The admin names are composed here rather
        // than defaulted in ConfigProvider because they depend on another component's
        // resolved prefix, which this component cannot know statically.
        /** @var RouteNames $routeNames */
        $routeNames = [
            'session.read'               => "{$routeNamePrefix}session.read",
            'session.create'             => "{$routeNamePrefix}session.create",
            'register.read'              => "{$routeNamePrefix}register.read",
            'register.create'            => "{$routeNamePrefix}register.create",
            'verify.email.read'          => "{$routeNamePrefix}verify.email.read",
            'resend.verification.read'   => "{$routeNamePrefix}resend.verification.read",
            'resend.verification.create' => "{$routeNamePrefix}resend.verification.create",
            'logout.read'                => "{$routeNamePrefix}logout.read",
            'admin.index'                => rtrim(
                string    : $adminRouteNamePrefix,
                characters: '.',
            ),
            'admin.create'               => "{$adminRouteNamePrefix}create",
            'admin.update'               => "{$adminRouteNamePrefix}update",
            'admin.update.modal'         => "{$adminRouteNamePrefix}update.modal",
            'admin.toggle.update'        => "{$adminRouteNamePrefix}toggle.update",
        ];

        return new RouteProvider(
            routeSegment     : $routeSegment,
            adminRouteSegment: $adminRouteSegment,
            routeNames       : [...$routeNames, ...$this->overrides($container)],
        );
    }
}
