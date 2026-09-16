<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Middleware\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Webware\Core\Exception;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Container\Configuration;
use Webware\UserManager\Http\Middleware\LoginMiddleware;

final class LoginMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws Exception\ExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): LoginMiddleware
    {
        return new LoginMiddleware(
            messageBus : $container->get(MessageBusInterface::class),
            logger     : $container->get(LoggerInterface::class),
            redirectUrl: Configuration::getPostLoginRedirect($container, self::class),
        );
    }
}
