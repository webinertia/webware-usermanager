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
use Webware\UserManager\Command\ResendVerificationEmailCommand;
use Webware\UserManager\CommandHandler\ResendVerificationEmailHandler;

#[CoversClass(ResendVerificationEmailHandler::class)]
#[CoversMethod(ResendVerificationEmailHandler::class, '__construct')]
#[CoversMethod(ResendVerificationEmailHandler::class, 'handle')]
final class ResendVerificationEmailHandlerTest extends TestCase
{
    #[Test]
    public function rendersAndSendsTheResendVerificationEmail(): void
    {
        $sent = null;

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->willReturnCallback(static function (MessageInterface $message) use (&$sent): bool {
                $sent = $message;

                return true;
            });

        $result = new ResendVerificationEmailHandler($mailer)->handle($this->command());

        static::assertSame(MessageStatus::Success, $result->getStatus());
        static::assertSame('Verification email sent.', $result->getResult());

        if (! $sent instanceof Message) {
            static::fail('Expected a Message to be handed to the mailer.');
        }

        static::assertSame([['jane@example.com', 'Jane Doe']], $sent->to);
        static::assertSame('Verify your email', $sent->subject);
        static::assertTrue($sent->html);
        static::assertSame(
            '<p>Hello Jane,</p>'
                . '<p>You requested a new verification link. Please verify your email address by clicking below.</p>'
                . '<p><a href="https://example.com/user/verify-email">Verify my email</a></p>'
                . '<p>This link expires in 24 hours.</p>',
            $sent->body,
        );
        static::assertSame(
            "Hello Jane,\n\nYou requested a new verification link. Please visit:\nhttps://example.com/user/verify-email\n\nThis link expires in 24 hours.\n",
            $sent->altBody,
        );
    }

    #[Test]
    public function reportsFailureWhenTheMailerCannotSend(): void
    {
        $mailer = $this->createStub(MailerInterface::class);
        $mailer->method('send')->willReturn(false);

        $result = new ResendVerificationEmailHandler($mailer)->handle($this->command());

        static::assertSame(MessageStatus::Failure, $result->getStatus());
        static::assertSame('Verification email could not be sent.', $result->getResult());
    }

    private function command(): ResendVerificationEmailCommand
    {
        return new ResendVerificationEmailCommand(
            to             : 'jane@example.com',
            toName         : 'Jane Doe',
            firstName      : 'Jane',
            verificationUrl: 'https://example.com/user/verify-email',
            subject        : 'Verify your email',
        );
    }
}
