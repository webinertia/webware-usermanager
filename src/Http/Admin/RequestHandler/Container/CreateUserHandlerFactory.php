<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Admin\RequestHandler\Container;

use InvalidArgumentException;
use Laminas\View\HelperPluginManager;
use Mezzio\Helper\Exception\ExceptionInterface as HelperException;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\UserManager\Http\Admin\RequestHandler\CreateUserHandler;
use Webware\UserManager\Http\RequestHandler\UserListHandler;
use Webware\UserManager\View\Helper\UserAdminUrl;

final class CreateUserHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws HelperException
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): CreateUserHandler
    {
        $helperManager = $container->get(HelperPluginManager::class);
        $userAdminUrl  = $helperManager->get(UserAdminUrl::class);

        return new CreateUserHandler(
            template   : $container->get(TemplateRendererInterface::class),
            listHandler: $container->get(UserListHandler::class),
            listUrl    : $userAdminUrl(''),
        );
    }
}
