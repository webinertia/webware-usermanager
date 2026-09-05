<?php

declare(strict_types=1);

namespace Webware\UserManager\Middleware\Container;

use Laminas\InputFilter\InputFilterPluginManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Webware\Core\UserInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\InputFilter\UserDataFilter;
use Webware\UserManager\Middleware\RegistrationMiddleware;

final class RegistrationMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): RegistrationMiddleware
    {
        return new RegistrationMiddleware(
            $container->get(MessageBusInterface::class),
            $container->get(TemplateRendererInterface::class),
            $container->get(InputFilterPluginManager::class)->get(UserDataFilter::class),
        );
    }
}
