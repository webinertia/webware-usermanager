<?php

declare(strict_types=1);

namespace Webware\UserManager\QueryHandler\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\UserManager\QueryHandler\FetchUserByIdHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

final readonly class FetchUserByIdHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): FetchUserByIdHandler
    {
        return new FetchUserByIdHandler(
            users: $container->get(UserRepositoryInterface::class),
        );
    }
}
