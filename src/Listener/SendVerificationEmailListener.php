<?php

declare(strict_types=1);

namespace Webware\UserManager\Listener;

use Webware\Mailer\MailerInterface;
use Webware\UserManager\Event\SendVerificationEmailEvent;
use Webware\UserManager\View\Helper\UserUrl;

use function htmlspecialchars;
use function rtrim;

final class SendVerificationEmailListener
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $fromEmail,
        private readonly string $fromName,
        private readonly string $baseUrl,
        private readonly string $verificationSubject,
        private readonly UserUrl $userUrl,
    ) {}

    public function __invoke(SendVerificationEmailEvent $event): void
    {
        $command         = $event->getTarget();
        $token           = $command->verificationToken;
        $verificationUrl = rtrim($this->baseUrl, '/')
        . ($this->userUrl)('verify.email.read', ['token' => $token]);

        $adapter = $this->mailer->getAdapter();

        if ($adapter === null) {
            return;
        }

        $adapter->from($this->fromEmail, $this->fromName)
            ->to($event->getEmail())
            ->subject($this->verificationSubject)
            ->isHtml(true)
            ->body(
                '<p>Hello '
                    . htmlspecialchars($command->firstName, ENT_QUOTES, 'UTF-8')
                    . ',</p>'
                    . '<p>Thank you for registering. Please verify your email address by clicking the link below.</p>'
                    . '<p><a href="'
                    . htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8')
                    . '">Verify my email</a></p>'
                    . '<p>This link expires in 24 hours.</p>',
            )
            ->altBody(
                'Hello '
                    . $command->firstName
                    . ",\n\n"
                    . "Please verify your email address by visiting the following link:\n"
                    . $verificationUrl
                    . "\n\n"
                    . "This link expires in 24 hours.\n",
            );

        $this->mailer->send();
    }
}
