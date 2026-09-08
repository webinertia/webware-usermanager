<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Admin\Middleware\Container;

use Laminas\InputFilter\InputFilterPluginManager;
use Psr\Container\ContainerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\Admin\Middleware\ProcessUpdateUserMiddleware;
use Webware\UserManager\InputFilter\UpdateUserDataFilter;

final class ProcessUpdateUserMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): ProcessUpdateUserMiddleware
    {
        $manager = $container->get(InputFilterPluginManager::class);
        return new ProcessUpdateUserMiddleware(
            messageBus: $container->get(MessageBusInterface::class),
            filter    : $manager->get(UpdateUserDataFilter::class),
        );
    }
}
