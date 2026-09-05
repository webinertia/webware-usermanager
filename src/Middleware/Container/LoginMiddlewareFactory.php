<?php

declare(strict_types=1);

namespace Webware\UserManager\Middleware\Container;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Webware\UserManager\Container\Configuration;
use Webware\UserManager\Middleware\LoginMiddleware;
use Webware\UserManager\Repository\UserRepositoryInterface;

final class LoginMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): LoginMiddleware
    {
        $config      = $container->get('config')['authentication'] ?? [];
        $redirectUrl = $config[Configuration::POST_LOGIN_REDIRECT_KEY] ?? Configuration::POST_LOGIN_REDIRECT_VALUE;

        return new LoginMiddleware(
            repository : $container->get(UserRepositoryInterface::class),
            logger     : $container->get(LoggerInterface::class),
            redirectUrl: $redirectUrl,
        );
    }
}
