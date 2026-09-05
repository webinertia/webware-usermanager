<?php

declare(strict_types=1);

namespace Webware\UserManager\CommandHandler\Container;

use Psr\Container\ContainerInterface;
use Webware\UserManager\CommandHandler\UpdateUserHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

final class UpdateUserHandlerFactory
{
    public function __invoke(ContainerInterface $container): UpdateUserHandler
    {
        return new UpdateUserHandler(users: $container->get(UserRepositoryInterface::class));
    }
}
