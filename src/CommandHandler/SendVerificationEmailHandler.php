<?php

declare(strict_types=1);

namespace Webware\UserManager\CommandHandler;

use Webware\Mailer\MailerInterface;
use Webware\Mailer\Message;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\SendVerificationEmailCommand;

use function htmlspecialchars;

use const ENT_QUOTES;

/**
 * Renders and sends the verification email. The message is built here rather
 * than by the caller, so the transport contract stays on this side of the bus.
 */
final class SendVerificationEmailHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
    ) {}

    public function handle(SendVerificationEmailCommand $command): CommandResult
    {
        $firstName = htmlspecialchars($command->firstName, flags: ENT_QUOTES, encoding: 'UTF-8');
        $verifyUrl = htmlspecialchars($command->verificationUrl, flags: ENT_QUOTES, encoding: 'UTF-8');

        $sent = $this->mailer->send(
            new Message()->withTo($command->to)
                ->withSubject($command->subject)
                ->withHtml()
                ->withBody(
                    "<p>Hello {$firstName},</p><p>Thank you for registering. Please verify your email address by clicking the link below.</p><p><a href=\"{$verifyUrl}\">Verify my email</a></p><p>This link expires in 24 hours.</p>",
                )
                ->withAltBody(
                    "Hello {$command->firstName},\n\nPlease verify your email address by visiting the following link:\n{$command->verificationUrl}\n\nThis link expires in 24 hours.\n",
                ),
        );

        if (! $sent) {
            return new CommandResult($command, MessageStatus::Failure, 'Verification email could not be sent.');
        }

        return new CommandResult($command, MessageStatus::Success, 'Verification email sent.');
    }
}
