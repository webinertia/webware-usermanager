<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\RequestHandler\Container;

use InvalidArgumentException;
use Laminas\View\HelperPluginManager;
use Mezzio\Helper\Exception\ExceptionInterface as HelperException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\UserManager\Http\RequestHandler\LogoutHandler;
use Webware\UserManager\View\Helper\UserUrl;

final class LogoutHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws HelperException
     * @throws InvalidArgumentException
     */
    public function __invoke(ContainerInterface $container): LogoutHandler
    {
        $helperManager = $container->get(HelperPluginManager::class);
        $userUrl       = $helperManager->get(UserUrl::class);

        return new LogoutHandler(
            loginUrl: $userUrl('session.read'),
        );
    }
}
