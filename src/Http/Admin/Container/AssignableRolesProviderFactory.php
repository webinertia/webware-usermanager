<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Admin\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\Admin\AssignableRolesProvider;

final class AssignableRolesProviderFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): AssignableRolesProvider
    {
        return new AssignableRolesProvider(
            messageBus: $container->get(MessageBusInterface::class),
        );
    }
}
