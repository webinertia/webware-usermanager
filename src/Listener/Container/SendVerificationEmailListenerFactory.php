<?php

declare(strict_types=1);

namespace Webware\UserManager\Listener\Container;

use Laminas\View\HelperPluginManager;
use Psr\Container\ContainerInterface;
use Webware\Mailer\MailerInterface;
use Webware\UserManager\Listener\SendVerificationEmailListener;
use Webware\UserManager\View\Helper\UserUrl;

final class SendVerificationEmailListenerFactory
{
    public function __invoke(ContainerInterface $container): SendVerificationEmailListener
    {
        /** @var array{user: array{from_email: string, from_name: string, base_url: string}, MailerInterface::class: array{verification_email_subject: string}} $config */
        $config     = $container->get('config');
        $userConf   = $config['user'] ?? [];
        $mailerConf = $config[MailerInterface::class] ?? [];

        /** @var HelperPluginManager $helperManager */
        $helperManager = $container->get(HelperPluginManager::class);

        return new SendVerificationEmailListener(
            mailer             : $container->get(MailerInterface::class),
            fromEmail          : (string) ($userConf['from_email'] ?? 'noreply@farmers-ims.local'),
            fromName           : (string) ($userConf['from_name'] ?? 'Farmers IMS'),
            baseUrl            : (string) ($userConf['base_url'] ?? 'http://localhost:8080'),
            verificationSubject: (string) ($mailerConf['verification_email_subject'] ?? 'Verify your account'),
            userUrl            : $helperManager->get(UserUrl::class),
        );
    }
}
