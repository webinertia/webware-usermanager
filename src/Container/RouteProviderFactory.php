<?php

declare(strict_types=1);

namespace Webware\UserManager\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Webware\Admin\Container\Configuration as AdminConfiguration;
use Webware\UserManager\RouteProvider;

final readonly class RouteProviderFactory
{
    /**
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): RouteProvider
    {
        $adminName = AdminConfiguration::getAdminName($container, self::class);

        return new RouteProvider(
            routeSegment        : Configuration::getRouteSegment(),
            routeNamePrefix     : Configuration::getRouteNamePrefix(),
            adminRouteSegment   : Configuration::getAdminRouteSegment($adminName),
            adminRouteNamePrefix: Configuration::getAdminRouteNamePrefix($adminName),
        );
    }
}
