<?php

declare(strict_types=1);

namespace Webware\UserManager\CommandHandler\Container;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\UserManager\CommandHandler\CreateUserHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

final class CreateUserHandlerFactory
{
    public function __invoke(ContainerInterface $container): CreateUserHandler
    {
        return new CreateUserHandler(
            users          : $container->get(UserRepositoryInterface::class),
            eventDispatcher: $container->get(EventDispatcherInterface::class),
        );
    }
}
