<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Middleware\Container;

use InvalidArgumentException;
use Laminas\View\HelperPluginManager;
use Mezzio\Helper\Exception\ExceptionInterface as HelperException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Core\Exception;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Container\Configuration;
use Webware\UserManager\Http\Middleware\ProcessVerifyEmailMiddleware;
use Webware\UserManager\View\Helper\UserUrl;

final readonly class ProcessVerifyEmailMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws Exception\ExceptionInterface
     * @throws HelperException
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ProcessVerifyEmailMiddleware
    {
        $helperManager = $container->get(HelperPluginManager::class);
        $userUrl       = $helperManager->get(UserUrl::class);

        return new ProcessVerifyEmailMiddleware(
            messageBus: $container->get(MessageBusInterface::class),
            userUrl   : $userUrl,
            tokenTtl  : Configuration::getVerificationTokenTtl($container, self::class),
        );
    }
}
