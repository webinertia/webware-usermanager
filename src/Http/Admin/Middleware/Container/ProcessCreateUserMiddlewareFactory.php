<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Admin\Middleware\Container;

use Laminas\InputFilter\InputFilterPluginManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\Admin\Middleware\ProcessCreateUserMiddleware;
use Webware\UserManager\InputFilter\CreateUserDataFilter;

final class ProcessCreateUserMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ProcessCreateUserMiddleware
    {
        $manager = $container->get(InputFilterPluginManager::class);

        return new ProcessCreateUserMiddleware(
            messageBus: $container->get(MessageBusInterface::class),
            filter    : $manager->get(CreateUserDataFilter::class),
            logger    : $container->get(LoggerInterface::class),
        );
    }
}
