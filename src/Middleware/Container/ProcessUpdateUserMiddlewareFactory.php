<?php

declare(strict_types=1);

namespace Webware\UserManager\Middleware\Container;

use Laminas\InputFilter\InputFilterPluginManager;
use Psr\Container\ContainerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\InputFilter\UserDataFilter;
use Webware\UserManager\Middleware\ProcessUpdateUserMiddleware;

final class ProcessUpdateUserMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): ProcessUpdateUserMiddleware
    {
        $manager = $container->get(InputFilterPluginManager::class);
        return new ProcessUpdateUserMiddleware(
            messageBus: $container->get(MessageBusInterface::class),
            filter    : $manager->get(UserDataFilter::class),
        );
    }
}
