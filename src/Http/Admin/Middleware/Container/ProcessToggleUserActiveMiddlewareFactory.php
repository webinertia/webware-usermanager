<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Admin\Middleware\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\Admin\Middleware\ProcessToggleUserActiveMiddleware;

final class ProcessToggleUserActiveMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ProcessToggleUserActiveMiddleware
    {
        return new ProcessToggleUserActiveMiddleware(messageBus: $container->get(MessageBusInterface::class));
    }
}
