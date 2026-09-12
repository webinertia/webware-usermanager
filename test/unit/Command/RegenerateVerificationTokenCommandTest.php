<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Command\RegenerateVerificationTokenCommand;

use function bin2hex;
use function random_bytes;

#[CoversClass(RegenerateVerificationTokenCommand::class)]
#[CoversMethod(RegenerateVerificationTokenCommand::class, '__construct')]
final class RegenerateVerificationTokenCommandTest extends TestCase
{
    #[Test]
    public function constructorAssignsIdTokenAndCreatedAt(): void
    {
        $token = bin2hex(random_bytes(16));

        $command = new RegenerateVerificationTokenCommand(
            id            : 42,
            token         : $token,
            tokenCreatedAt: '2026-09-08 12:00:00',
        );

        static::assertSame(42, $command->id);
        static::assertSame($token, $command->token);
        static::assertSame('2026-09-08 12:00:00', $command->tokenCreatedAt);
    }

    #[Test]
    public function getNameReturnsFullyQualifiedClassName(): void
    {
        $command = new RegenerateVerificationTokenCommand(
            id            : 1,
            token         : bin2hex(random_bytes(16)),
            tokenCreatedAt: '2026-09-08 12:00:00',
        );

        static::assertSame(RegenerateVerificationTokenCommand::class, $command->getName());
    }
}
