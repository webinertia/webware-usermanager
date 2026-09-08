<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\RequestHandler\Container;

use Laminas\View\HelperPluginManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\UserManager\Http\RequestHandler\VerifyEmailHandler;
use Webware\UserManager\View\Helper\UserUrl;

final readonly class VerifyEmailHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): VerifyEmailHandler
    {
        $helperManager = $container->get(HelperPluginManager::class);
        $userUrl       = $helperManager->get(UserUrl::class);

        return new VerifyEmailHandler(
            template: $container->get(TemplateRendererInterface::class),
            loginUrl: $userUrl('session.read'),
        );
    }
}
