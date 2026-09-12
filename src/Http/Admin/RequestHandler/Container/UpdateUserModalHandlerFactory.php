<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\Admin\RequestHandler\UpdateUserModalHandler;

final class UpdateUserModalHandlerFactory
{
    public function __invoke(ContainerInterface $container): UpdateUserModalHandler
    {
        return new UpdateUserModalHandler(
            template  : $container->get(TemplateRendererInterface::class),
            messageBus: $container->get(MessageBusInterface::class),
        );
    }
}
