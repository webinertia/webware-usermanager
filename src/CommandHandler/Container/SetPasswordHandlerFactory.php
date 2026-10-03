<?php

declare(strict_types=1);

namespace Webware\UserManager\CommandHandler\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\UserManager\CommandHandler\SetPasswordHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

final class SetPasswordHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): SetPasswordHandler
    {
        return new SetPasswordHandler(
            users: $container->get(UserRepositoryInterface::class),
        );
    }
}
