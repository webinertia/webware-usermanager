<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Core\Exception;
use Webware\UserManager\Container\Configuration;
use Webware\UserManager\Http\RequestHandler\LoginHandler;

final class LoginHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws Exception\ExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): LoginHandler
    {
        return new LoginHandler(
            template       : $container->get(TemplateRendererInterface::class),
            credentialField: Configuration::getUsernameFieldName($container, self::class),
            passwordField  : Configuration::getPasswordFieldName($container, self::class),
        );
    }
}
