<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Webware\UserManager\Http\RequestHandler\UserListHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

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
