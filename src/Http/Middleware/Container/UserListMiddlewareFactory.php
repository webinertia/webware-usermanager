<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Middleware\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\Middleware\UserListMiddleware;

final readonly class UserListMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): UserListMiddleware
    {
        return new UserListMiddleware(
            messageBus: $container->get(MessageBusInterface::class),
        );
    }
}
