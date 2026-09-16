<?php

declare(strict_types=1);

namespace Webware\UserManager\CommandHandler;

use Webware\Mailer\MailerInterface;
use Webware\Mailer\Message;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\ResendVerificationEmailCommand;

use function htmlspecialchars;

use const ENT_QUOTES;

/**
 * Renders and sends the resend variant of the verification email.
 */
final class ResendVerificationEmailHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
    ) {}

    public function handle(ResendVerificationEmailCommand $command): CommandResult
    {
        $firstName = htmlspecialchars($command->firstName, flags: ENT_QUOTES, encoding: 'UTF-8');
        $verifyUrl = htmlspecialchars($command->verificationUrl, flags: ENT_QUOTES, encoding: 'UTF-8');

        $sent = $this->mailer->send(
            new Message()->withTo($command->to, $command->toName)
                ->withSubject($command->subject)
                ->withHtml()
                ->withBody(
                    "<p>Hello {$firstName},</p><p>You requested a new verification link. Please verify your email address by clicking below.</p><p><a href=\"{$verifyUrl}\">Verify my email</a></p><p>This link expires in 24 hours.</p>",
                )
                ->withAltBody(
                    "Hello {$command->firstName},\n\nYou requested a new verification link. Please visit:\n{$command->verificationUrl}\n\nThis link expires in 24 hours.\n",
                ),
        );

        if (! $sent) {
            return new CommandResult($command, MessageStatus::Failure, 'Verification email could not be sent.');
        }

        return new CommandResult($command, MessageStatus::Success, 'Verification email sent.');
    }
}
