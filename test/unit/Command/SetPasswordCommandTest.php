<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\Command\NamedCommandInterface;
use Webware\UserManager\Command\SetPasswordCommand;

use function password_verify;

#[CoversClass(SetPasswordCommand::class)]
#[CoversMethod(SetPasswordCommand::class, '__construct')]
final class SetPasswordCommandTest extends TestCase
{
    #[Test]
    public function hashesThePlaintextPassword(): void
    {
        $command = new SetPasswordCommand(
            id          : 4,
            passwordHash: 'correct horse battery staple',
        );

        static::assertNotSame('correct horse battery staple', $command->passwordHash);
        static::assertTrue(password_verify('correct horse battery staple', $command->passwordHash));
    }

    #[Test]
    public function isANamedCommand(): void
    {
        $command = new SetPasswordCommand(
            id          : 4,
            passwordHash: 'correct horse battery staple',
        );

        static::assertInstanceOf(NamedCommandInterface::class, $command);
    }
}
