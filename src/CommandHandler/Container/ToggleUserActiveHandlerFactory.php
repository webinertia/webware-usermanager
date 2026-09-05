<?php

declare(strict_types=1);

namespace Webware\UserManager\CommandHandler\Container;

use Psr\Container\ContainerInterface;
use Webware\UserManager\CommandHandler\ToggleUserActiveHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

final class ToggleUserActiveHandlerFactory
{
    public function __invoke(ContainerInterface $container): ToggleUserActiveHandler
    {
        return new ToggleUserActiveHandler(users: $container->get(UserRepositoryInterface::class));
    }
}
