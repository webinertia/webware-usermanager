<?php

declare(strict_types=1);

namespace Webware\UserManager\QueryHandler\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Core\AclInterface;
use Webware\UserManager\QueryHandler\FetchAssignableRolesHandler;

final readonly class FetchAssignableRolesHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): FetchAssignableRolesHandler
    {
        return new FetchAssignableRolesHandler(
            acl: $container->get(AclInterface::class),
        );
    }
}
