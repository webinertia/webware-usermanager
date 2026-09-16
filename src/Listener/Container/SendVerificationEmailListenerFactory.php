<?php

declare(strict_types=1);

namespace Webware\UserManager\Listener\Container;

use Laminas\View\HelperPluginManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Core\Exception;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Container\Configuration;
use Webware\UserManager\Listener\SendVerificationEmailListener;
use Webware\UserManager\View\Helper\UserUrl;

final class SendVerificationEmailListenerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws Exception\ExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): SendVerificationEmailListener
    {
        $helperManager = $container->get(HelperPluginManager::class);
        $userUrl       = $helperManager->get(UserUrl::class);

        return new SendVerificationEmailListener(
            messageBus         : $container->get(MessageBusInterface::class),
            baseUrl            : Configuration::getBaseUrl($container, self::class),
            verificationSubject: Configuration::getVerificationEmailSubject($container, self::class),
            userUrl            : $userUrl,
        );
    }
}
