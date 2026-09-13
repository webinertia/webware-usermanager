<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Middleware\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Core\UserInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\Middleware\IdentityMiddleware;

final readonly class IdentityMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
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
