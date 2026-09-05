<?php

declare(strict_types=1);

namespace Webware\UserManager\Container;

use Psr\Container\ContainerInterface;
use Webware\Admin\Container\Configuration as AdminConfiguration;
use Webware\UserManager\RouteProvider;

final readonly class RouteProviderFactory
{
    public function __invoke(ContainerInterface $container): RouteProvider
    {
        $routeSegment    = Configuration::getRouteSegment($container, self::class);
        $routeNamePrefix = Configuration::getRouteNamePrefix($container, self::class);

        $adminBaseRouteSegment      = AdminConfiguration::getAdminRouteSegment($container, self::class);
        $moduleAdminRouteSegment    = Configuration::getAdminRouteSegment($container, self::class);
        $adminBaseRouteNamePrefix   = AdminConfiguration::getAdminRouteNamePrefix($container, self::class);
        $moduleAdminRouteNamePrefix = Configuration::getAdminRouteNamePrefix($container, self::class);

        return new RouteProvider(
            $routeSegment,
            $routeNamePrefix,
            $adminBaseRouteSegment . '/' . $moduleAdminRouteSegment,
            $adminBaseRouteNamePrefix . $moduleAdminRouteNamePrefix,
        );
    }
}
