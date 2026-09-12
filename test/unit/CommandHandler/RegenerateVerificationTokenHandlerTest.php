<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\RegenerateVerificationTokenCommand;
use Webware\UserManager\CommandHandler\RegenerateVerificationTokenHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

use function bin2hex;
use function random_bytes;

#[CoversClass(RegenerateVerificationTokenHandler::class)]
#[CoversMethod(RegenerateVerificationTokenHandler::class, '__construct')]
#[CoversMethod(RegenerateVerificationTokenHandler::class, 'handle')]
final class RegenerateVerificationTokenHandlerTest extends TestCase
{
    #[Test]
    public function updatesUserWithNewToken(): void
    {
        $token = bin2hex(random_bytes(16));
        $now   = '2026-09-08 12:00:00';

        $users = $this->createMock(UserRepositoryInterface::class);
        $users->expects($this->once())
            ->method('update')
            ->with(7, ['verificationToken' => $token, 'tokenCreatedAt' => $now])
            ->willReturn(1);

        $command = new RegenerateVerificationTokenCommand(
            id            : 7,
            token         : $token,
            tokenCreatedAt: $now,
        );

        $result = new RegenerateVerificationTokenHandler($users)->handle($command);

        static::assertSame(MessageStatus::Success, $result->getStatus());
        static::assertSame(1, $result->getResult());
    }
}
