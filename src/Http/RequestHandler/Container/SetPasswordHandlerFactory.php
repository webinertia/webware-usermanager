<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\RequestHandler\Container;

use InvalidArgumentException;
use Laminas\View\HelperPluginManager;
use Mezzio\Helper\Exception\ExceptionInterface as HelperException;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\UserManager\Http\RequestHandler\SetPasswordHandler;
use Webware\UserManager\View\Helper\UserUrl;

final readonly class SetPasswordHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws HelperException
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): SetPasswordHandler
    {
        $helperManager = $container->get(HelperPluginManager::class);
        $userUrl       = $helperManager->get(UserUrl::class);

        return new SetPasswordHandler(
            template: $container->get(TemplateRendererInterface::class),
            loginUrl: $userUrl('session.read'),
        );
    }
}
