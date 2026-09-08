<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Middleware\Container;

use Laminas\View\HelperPluginManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Mailer\MailerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\Middleware\ProcessResendVerificationMiddleware;
use Webware\UserManager\View\Helper\UserUrl;

final readonly class ProcessResendVerificationMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ProcessResendVerificationMiddleware
    {
        /** @var array<string, mixed> $config */
        $config = $container->get('config');

        /** @var array<string, mixed> $userConf */
        $userConf = $config['user'] ?? [];

        /** @var array<string, mixed> $mailerConf */
        $mailerConf = $config[MailerInterface::class] ?? [];

        $helperManager = $container->get(HelperPluginManager::class);
        $userUrl       = $helperManager->get(UserUrl::class);

        $mailConfig = [
            'from_email'                 => (string) ($userConf['from_email'] ?? 'noreply@farmers-ims.local'),
            'from_name'                  => (string) ($userConf['from_name'] ?? 'Farmers IMS'),
            'base_url'                   => (string) ($userConf['base_url'] ?? 'http://localhost:8080'),
            'verification_email_subject' => (string) (
                $mailerConf['verification_email_subject'] ?? 'Verify your account'
            ),
        ];

        return new ProcessResendVerificationMiddleware(
            messageBus: $container->get(MessageBusInterface::class),
            mailer    : $container->get(MailerInterface::class),
            userUrl   : $userUrl,
            mailConfig: $mailConfig,
        );
    }
}
