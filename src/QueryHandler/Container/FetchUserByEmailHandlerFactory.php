<?php

declare(strict_types=1);

namespace Webware\UserManager\QueryHandler\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\UserManager\QueryHandler\FetchUserByEmailHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

final readonly class FetchUserByEmailHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): FetchUserByEmailHandler
    {
        return new FetchUserByEmailHandler(
            users: $container->get(UserRepositoryInterface::class),
        );
    }
}
