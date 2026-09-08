<?php

declare(strict_types=1);

namespace Webware\UserManager\CommandHandler\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\UserManager\CommandHandler\ActivateUserHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

final readonly class ActivateUserHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ActivateUserHandler
    {
        return new ActivateUserHandler(
            users: $container->get(UserRepositoryInterface::class),
        );
    }
}
