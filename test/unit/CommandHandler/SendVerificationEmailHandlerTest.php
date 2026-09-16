<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Mailer\Adapter\MessageInterface;
use Webware\Mailer\MailerInterface;
use Webware\Mailer\Message;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\SendVerificationEmailCommand;
use Webware\UserManager\CommandHandler\SendVerificationEmailHandler;

#[CoversClass(SendVerificationEmailHandler::class)]
#[CoversMethod(SendVerificationEmailHandler::class, '__construct')]
#[CoversMethod(SendVerificationEmailHandler::class, 'handle')]
final class SendVerificationEmailHandlerTest extends TestCase
{
    #[Test]
    public function escapesTheNameAndLinkInTheHtmlBody(): void
    {
        $sent = null;

        $mailer = $this->createStub(MailerInterface::class);
        $mailer->method('send')
            ->willReturnCallback(static function (MessageInterface $message) use (&$sent): bool {
                $sent = $message;

                return true;
            });

        new SendVerificationEmailHandler($mailer)->handle(new SendVerificationEmailCommand(
            to             : 'jane@example.com',
            firstName      : 'Jane <b>',
            verificationUrl: 'https://example.com/v?t=1&x=2',
            subject        : 'Verify your email',
        ));

        if (! $sent instanceof Message) {
            static::fail('Expected a Message to be handed to the mailer.');
        }

        static::assertStringContainsString('<p>Hello Jane &lt;b&gt;,</p>', $sent->body);
        static::assertStringContainsString('t=1&amp;x=2', $sent->body);

        // The plain-text alternative carries the raw values.
        static::assertStringContainsString('Hello Jane <b>,', $sent->altBody);
        static::assertStringContainsString('t=1&x=2', $sent->altBody);
    }

    #[Test]
    public function rendersAndSendsTheVerificationEmail(): void
    {
        $sent = null;

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->willReturnCallback(static function (MessageInterface $message) use (&$sent): bool {
                $sent = $message;

                return true;
            });

        $result = new SendVerificationEmailHandler($mailer)->handle($this->command());

        static::assertSame(MessageStatus::Success, $result->getStatus());
        static::assertSame('Verification email sent.', $result->getResult());

        if (! $sent instanceof Message) {
            static::fail('Expected a Message to be handed to the mailer.');
        }

        static::assertSame([['jane@example.com', '']], $sent->to);
        static::assertSame('Verify your email', $sent->subject);
        static::assertTrue($sent->html);
        static::assertSame(
            '<p>Hello Jane,</p>'
                . '<p>Thank you for registering. Please verify your email address by clicking the link below.</p>'
                . '<p><a href="https://example.com/user/verify-email">Verify my email</a></p>'
                . '<p>This link expires in 24 hours.</p>',
            $sent->body,
        );
        static::assertSame(
            "Hello Jane,\n\nPlease verify your email address by visiting the following link:\nhttps://example.com/user/verify-email\n\nThis link expires in 24 hours.\n",
            $sent->altBody,
        );
    }

    #[Test]
    public function reportsFailureWhenTheMailerCannotSend(): void
    {
        $mailer = $this->createStub(MailerInterface::class);
        $mailer->method('send')->willReturn(false);

        $result = new SendVerificationEmailHandler($mailer)->handle($this->command());

        static::assertSame(MessageStatus::Failure, $result->getStatus());
        static::assertSame('Verification email could not be sent.', $result->getResult());
    }

    private function command(): SendVerificationEmailCommand
    {
        return new SendVerificationEmailCommand(
            to             : 'jane@example.com',
            firstName      : 'Jane',
            verificationUrl: 'https://example.com/user/verify-email',
            subject        : 'Verify your email',
        );
    }
}
