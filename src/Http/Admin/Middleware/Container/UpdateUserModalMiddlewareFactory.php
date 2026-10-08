<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Admin\Middleware\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\Admin\AssignableRolesProvider;
use Webware\UserManager\Http\Admin\Middleware\UpdateUserModalMiddleware;

final readonly class UpdateUserModalMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): UpdateUserModalMiddleware
    {
        return new UpdateUserModalMiddleware(
            messageBus     : $container->get(MessageBusInterface::class),
            assignableRoles: $container->get(AssignableRolesProvider::class),
        );
    }
}
