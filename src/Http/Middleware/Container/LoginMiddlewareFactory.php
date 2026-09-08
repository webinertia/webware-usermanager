<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Middleware\Container;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Container\Configuration;
use Webware\UserManager\Http\Middleware\LoginMiddleware;

final class LoginMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): LoginMiddleware
    {
        $config      = $container->get('config')['authentication'] ?? [];
        $redirectUrl = $config[Configuration::POST_LOGIN_REDIRECT_KEY] ?? Configuration::POST_LOGIN_REDIRECT_VALUE;

        return new LoginMiddleware(
            messageBus : $container->get(MessageBusInterface::class),
            logger     : $container->get(LoggerInterface::class),
            redirectUrl: $redirectUrl,
        );
    }
}
