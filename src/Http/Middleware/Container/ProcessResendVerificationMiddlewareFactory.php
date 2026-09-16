<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Middleware\Container;

use Laminas\View\HelperPluginManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Core\Exception;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Container\Configuration;
use Webware\UserManager\Http\Middleware\ProcessResendVerificationMiddleware;
use Webware\UserManager\View\Helper\UserUrl;

final readonly class ProcessResendVerificationMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws Exception\ExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ProcessResendVerificationMiddleware
    {
        $helperManager = $container->get(HelperPluginManager::class);
        $userUrl       = $helperManager->get(UserUrl::class);

        return new ProcessResendVerificationMiddleware(
            messageBus         : $container->get(MessageBusInterface::class),
            userUrl            : $userUrl,
            baseUrl            : Configuration::getBaseUrl($container, self::class),
            verificationSubject: Configuration::getVerificationEmailSubject($container, self::class),
        );
    }
}
