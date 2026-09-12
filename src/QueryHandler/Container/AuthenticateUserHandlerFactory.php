<?php

declare(strict_types=1);

namespace Webware\UserManager\QueryHandler\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\UserManager\QueryHandler\AuthenticateUserHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

final readonly class AuthenticateUserHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): AuthenticateUserHandler
    {
        return new AuthenticateUserHandler(
            users: $container->get(UserRepositoryInterface::class),
        );
    }
}
