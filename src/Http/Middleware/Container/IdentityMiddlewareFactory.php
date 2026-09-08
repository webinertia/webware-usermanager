<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Middleware\Container;

use Psr\Container\ContainerInterface;
use Webware\Core\UserInterface;
use Webware\UserManager\Http\Middleware\IdentityMiddleware;
use Webware\UserManager\Repository\UserRepositoryInterface;

final readonly class IdentityMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): IdentityMiddleware
    {
        return new IdentityMiddleware(
            repository : $container->get(UserRepositoryInterface::class),
            userFactory: $container->get(UserInterface::class),
        );
    }
}
