<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Middleware\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\Middleware\ProcessVerifyEmailMiddleware;

final readonly class ProcessVerifyEmailMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ProcessVerifyEmailMiddleware
    {
        /** @var array<string, mixed> $config */
        $config   = $container->get('config');
        $tokenTtl = (int) ($config['user']['verification_token_ttl'] ?? 86_400);

        return new ProcessVerifyEmailMiddleware(
            messageBus: $container->get(MessageBusInterface::class),
            tokenTtl  : $tokenTtl,
        );
    }
}
