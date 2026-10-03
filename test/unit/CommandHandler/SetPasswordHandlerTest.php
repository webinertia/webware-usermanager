<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\SetPasswordCommand;
use Webware\UserManager\CommandHandler\SetPasswordHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

#[CoversClass(SetPasswordHandler::class)]
#[CoversMethod(SetPasswordHandler::class, '__construct')]
#[CoversMethod(SetPasswordHandler::class, 'handle')]
final class SetPasswordHandlerTest extends TestCase
{
    #[Test]
    public function writesTheHashAndClearsTheFlag(): void
    {
        $command = new SetPasswordCommand(
            id          : 4,
            passwordHash: 'correct horse battery staple',
        );

        $users = $this->createMock(UserRepositoryInterface::class);
        $users->expects($this->once())
            ->method('update')
            ->with(4, [
                'passwordHash'        => $command->passwordHash,
                'passwordSetRequired' => 0,
            ])
            ->willReturn(1);

        $result = new SetPasswordHandler($users)->handle($command);

        static::assertSame(MessageStatus::Success, $result->getStatus());
        static::assertSame(1, $result->getResult());
    }
}
