<?php

declare(strict_types=1);

namespace Webware\UserManager\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Webware\UserManager\Admin\RequestHandler\UpdateUserModalHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

final class UpdateUserModalHandlerFactory
{
    public function __invoke(ContainerInterface $container): UpdateUserModalHandler
    {
        return new UpdateUserModalHandler(
            template: $container->get(TemplateRendererInterface::class),
            users   : $container->get(UserRepositoryInterface::class),
        );
    }
}
