<?php

declare(strict_types=1);

namespace Webware\UserManager\View\Helper;

use Mezzio\Helper\UrlHelper;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\UserManager\Container\Configuration;

final readonly class UserUrlFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): UserUrl
    {
        return new UserUrl(
            urlHelper      : $container->get(UrlHelper::class),
            routeNamePrefix: Configuration::getRouteNamePrefix($container, self::class),
        );
    }
}
