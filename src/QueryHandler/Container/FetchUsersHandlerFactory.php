<?php

declare(strict_types=1);

namespace Webware\UserManager\QueryHandler\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\UserManager\QueryHandler\FetchUsersHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

final readonly class FetchUsersHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): FetchUsersHandler
    {
        return new FetchUsersHandler(
            users: $container->get(UserRepositoryInterface::class),
        );
    }
}
