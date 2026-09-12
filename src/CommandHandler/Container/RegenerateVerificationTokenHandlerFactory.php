<?php

declare(strict_types=1);

namespace Webware\UserManager\CommandHandler\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\UserManager\CommandHandler\RegenerateVerificationTokenHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

final readonly class RegenerateVerificationTokenHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): RegenerateVerificationTokenHandler
    {
        return new RegenerateVerificationTokenHandler(
            users: $container->get(UserRepositoryInterface::class),
        );
    }
}
