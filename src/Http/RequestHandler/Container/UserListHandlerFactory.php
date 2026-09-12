<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\RequestHandler\UserListHandler;

final class UserListHandlerFactory
{
    public function __invoke(ContainerInterface $container): UserListHandler
    {
        return new UserListHandler(
            template  : $container->get(TemplateRendererInterface::class),
            messageBus: $container->get(MessageBusInterface::class),
        );
    }
}
