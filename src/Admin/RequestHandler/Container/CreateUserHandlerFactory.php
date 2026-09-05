<?php

declare(strict_types=1);

namespace Webware\UserManager\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Webware\UserManager\Admin\RequestHandler\CreateUserHandler;

final class CreateUserHandlerFactory
{
    public function __invoke(ContainerInterface $container): CreateUserHandler
    {
        return new CreateUserHandler(
            template: $container->get(TemplateRendererInterface::class),
        );
    }
}
