<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Middleware\Container;

use Psr\Container\ContainerInterface;
use Webware\Core\UserInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\Middleware\IdentityMiddleware;

final readonly class IdentityMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): IdentityMiddleware
    {
        /** @var callable(array<string, mixed>): UserInterface $userFactory */
        $userFactory = $container->get(UserInterface::class);

        return new IdentityMiddleware(
            messageBus : $container->get(MessageBusInterface::class),
            userFactory: $userFactory,
        );
    }
}
