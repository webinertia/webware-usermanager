<?php

declare(strict_types=1);

namespace Webware\UserManager\View\Helper;

use Mezzio\Helper\UrlHelper;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Admin\Container\Configuration as AdminConfiguration;
use Webware\UserManager\Container\Configuration;

final readonly class UserAdminUrlFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): UserAdminUrl
    {
        $adminBasePrefix   = AdminConfiguration::getAdminRouteNamePrefix($container, self::class);
        $moduleAdminPrefix = Configuration::getAdminRouteNamePrefix($container, self::class);

        return new UserAdminUrl(
            urlHelper      : $container->get(UrlHelper::class),
            routeNamePrefix: $adminBasePrefix . $moduleAdminPrefix,
        );
    }
}
