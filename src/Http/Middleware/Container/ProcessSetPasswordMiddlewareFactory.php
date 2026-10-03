<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Middleware\Container;

use Laminas\InputFilter\InputFilterPluginManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Core\Exception;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Container\Configuration;
use Webware\UserManager\Http\Middleware\ProcessSetPasswordMiddleware;
use Webware\UserManager\InputFilter\SetPasswordDataFilter;

final class ProcessSetPasswordMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws Exception\ExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ProcessSetPasswordMiddleware
    {
        $manager = $container->get(InputFilterPluginManager::class);

        return new ProcessSetPasswordMiddleware(
            messageBus: $container->get(MessageBusInterface::class),
            filter    : $manager->get(SetPasswordDataFilter::class),
            tokenTtl  : Configuration::getVerificationTokenTtl($container, self::class),
        );
    }
}
