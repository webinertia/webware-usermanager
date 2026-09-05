<?php

declare(strict_types=1);

namespace Webware\UserManager\Middleware\Container;

use Psr\Container\ContainerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Middleware\ProcessToggleUserActiveMiddleware;

final class ProcessToggleUserActiveMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): ProcessToggleUserActiveMiddleware
    {
        return new ProcessToggleUserActiveMiddleware(messageBus: $container->get(MessageBusInterface::class));
    }
}
