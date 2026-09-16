<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Middleware\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Core\Exception;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Container\Configuration;
use Webware\UserManager\Http\Middleware\ProcessVerifyEmailMiddleware;

final readonly class ProcessVerifyEmailMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws Exception\ExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ProcessVerifyEmailMiddleware
    {
        return new ProcessVerifyEmailMiddleware(
            messageBus: $container->get(MessageBusInterface::class),
            tokenTtl  : Configuration::getVerificationTokenTtl($container, self::class),
        );
    }
}
