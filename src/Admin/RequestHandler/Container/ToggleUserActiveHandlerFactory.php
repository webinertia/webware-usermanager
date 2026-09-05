<?php

declare(strict_types=1);

namespace Webware\UserManager\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Webware\UserManager\Admin\RequestHandler\ToggleUserActiveHandler;

final class ToggleUserActiveHandlerFactory
{
    public function __invoke(ContainerInterface $container): ToggleUserActiveHandler
    {
        return new ToggleUserActiveHandler(template: $container->get(TemplateRendererInterface::class));
    }
}
