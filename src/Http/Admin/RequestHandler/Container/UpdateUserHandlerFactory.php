<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Admin\RequestHandler\Container;

use InvalidArgumentException;
use Laminas\View\HelperPluginManager;
use Mezzio\Helper\Exception\ExceptionInterface as HelperException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\UserManager\Http\Admin\RequestHandler\UpdateUserHandler;
use Webware\UserManager\Http\RequestHandler\UserListHandler;
use Webware\UserManager\View\Helper\UserAdminUrl;

final class UpdateUserHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws HelperException
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): UpdateUserHandler
    {
        $helperManager = $container->get(HelperPluginManager::class);
        $userAdminUrl  = $helperManager->get(UserAdminUrl::class);

        return new UpdateUserHandler(
            listHandler: $container->get(UserListHandler::class),
            listUrl    : $userAdminUrl(''),
        );
    }
}
