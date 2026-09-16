<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Command\SendVerificationEmailCommand;

#[CoversClass(SendVerificationEmailCommand::class)]
#[CoversMethod(SendVerificationEmailCommand::class, '__construct')]
final class SendVerificationEmailCommandTest extends TestCase
{
    #[Test]
    public function constructorAssignsTheEmailPayload(): void
    {
        $command = new SendVerificationEmailCommand(
            to             : 'jane@example.com',
            firstName      : 'Jane',
            verificationUrl: 'https://example.com/user/verify-email',
            subject        : 'Verify your email',
        );

        static::assertSame('jane@example.com', $command->to);
        static::assertSame('Jane', $command->firstName);
        static::assertSame('https://example.com/user/verify-email', $command->verificationUrl);
        static::assertSame('Verify your email', $command->subject);
    }

    #[Test]
    public function getNameReturnsFullyQualifiedClassName(): void
    {
        $command = new SendVerificationEmailCommand(
            to             : 'jane@example.com',
            firstName      : 'Jane',
            verificationUrl: 'https://example.com/user/verify-email',
            subject        : 'Verify your email',
        );

        static::assertSame(SendVerificationEmailCommand::class, $command->getName());
    }
}
