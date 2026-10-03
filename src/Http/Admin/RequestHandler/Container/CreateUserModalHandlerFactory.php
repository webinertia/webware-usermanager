<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\Admin\RequestHandler\CreateUserModalHandler;

final class CreateUserModalHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): CreateUserModalHandler
    {
        return new CreateUserModalHandler(
            template  : $container->get(TemplateRendererInterface::class),
            messageBus: $container->get(MessageBusInterface::class),
        );
    }
}
