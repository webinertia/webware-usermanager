<?php

declare(strict_types=1);

namespace Webware\UserManager\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Webware\UserManager\Repository\UserRepositoryInterface;
use Webware\UserManager\RequestHandler\UserListHandler;

final class UserListHandlerFactory
{
    public function __invoke(ContainerInterface $container): UserListHandler
    {
        return new UserListHandler(
            template: $container->get(TemplateRendererInterface::class),
            users   : $container->get(UserRepositoryInterface::class),
        );
    }
}
