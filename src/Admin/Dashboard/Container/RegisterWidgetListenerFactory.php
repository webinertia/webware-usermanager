<?php

declare(strict_types=1);

namespace Webware\UserManager\Admin\Dashboard\Container;

use Psr\Container\ContainerInterface;
use Webware\Admin\Container\Configuration as AdminConfiguration;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Admin\Dashboard\RegisterWidgetListener;
use Webware\UserManager\Container\Configuration;

use function rtrim;

final readonly class RegisterWidgetListenerFactory
{
    public function __invoke(ContainerInterface $container): RegisterWidgetListener
    {
        $resourceId = rtrim(
            AdminConfiguration::getAdminRouteNamePrefix($container, self::class)
                . Configuration::getAdminRouteNamePrefix($container, self::class),
            '.',
        );

        return new RegisterWidgetListener(
            resourceId: $resourceId,
            messageBus: $container->get(MessageBusInterface::class),
        );
    }
}
