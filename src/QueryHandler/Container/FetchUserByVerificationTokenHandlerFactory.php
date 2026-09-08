<?php

declare(strict_types=1);

namespace Webware\UserManager\QueryHandler\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\UserManager\QueryHandler\FetchUserByVerificationTokenHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

final readonly class FetchUserByVerificationTokenHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): FetchUserByVerificationTokenHandler
    {
        return new FetchUserByVerificationTokenHandler(
            users: $container->get(UserRepositoryInterface::class),
        );
    }
}
