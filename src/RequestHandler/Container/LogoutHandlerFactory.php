<?php

declare(strict_types=1);

namespace Webware\UserManager\RequestHandler\Container;

use Laminas\View\HelperPluginManager;
use Psr\Container\ContainerInterface;
use Webware\UserManager\RequestHandler\LogoutHandler;
use Webware\UserManager\View\Helper\UserUrl;

final class LogoutHandlerFactory
{
    public function __invoke(ContainerInterface $container): LogoutHandler
    {
        /** @var HelperPluginManager $helperManager */
        $helperManager = $container->get(HelperPluginManager::class);
        $userUrl       = $helperManager->get(UserUrl::class);

        return new LogoutHandler(
            loginUrl: $userUrl('session.read'),
        );
    }
}
