<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Middleware\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Container\Configuration;
use Webware\UserManager\Http\Middleware\LoginMiddleware;

final class LoginMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): LoginMiddleware
    {
        /** @var array<string, mixed> $config */
        $config = $container->get('config')['authentication'] ?? [];

        /** @var string $redirectUrl */
        $redirectUrl = $config[Configuration::POST_LOGIN_REDIRECT_KEY] ?? Configuration::POST_LOGIN_REDIRECT_VALUE;

        return new LoginMiddleware(
            messageBus : $container->get(MessageBusInterface::class),
            logger     : $container->get(LoggerInterface::class),
            redirectUrl: $redirectUrl,
        );
    }
}
