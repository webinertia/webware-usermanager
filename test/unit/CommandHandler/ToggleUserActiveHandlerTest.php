<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\ToggleUserActiveCommand;
use Webware\UserManager\CommandHandler\ToggleUserActiveHandler;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Repository\UserRepositoryInterface;

#[CoversClass(ToggleUserActiveHandler::class)]
#[CoversMethod(ToggleUserActiveHandler::class, '__construct')]
#[CoversMethod(ToggleUserActiveHandler::class, 'handle')]
final class ToggleUserActiveHandlerTest extends TestCase
{
    #[Test]
    public function returnsFailureWhenUserNotFound(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn(null);

        $result = new ToggleUserActiveHandler($users)->handle(new ToggleUserActiveCommand(id: 999));

        static::assertSame(MessageStatus::Failure, $result->getStatus());
        static::assertSame('User not found.', $result->getResult());
    }

    #[Test]
    public function togglesActiveUserToInactive(): void
    {
        $user = new User(
            id    : 1,
            active: true,
        );

        $users = $this->createMock(UserRepositoryInterface::class);
        $users->method('findById')->willReturn($user);
        $users->expects($this->once())
            ->method('update')
            ->with(1, ['active' => 0])
            ->willReturn(1);

        $result = new ToggleUserActiveHandler($users)->handle(new ToggleUserActiveCommand(id: 1));

        static::assertSame(MessageStatus::Success, $result->getStatus());
    }

    #[Test]
    public function togglesInactiveUserToActive(): void
    {
        $user = new User(
            id    : 1,
            active: false,
        );

        $users = $this->createMock(UserRepositoryInterface::class);
        $users->method('findById')->willReturn($user);
        $users->expects($this->once())
            ->method('update')
            ->with(1, ['active' => 1])
            ->willReturn(1);

        $result = new ToggleUserActiveHandler($users)->handle(new ToggleUserActiveCommand(id: 1));

        static::assertSame(MessageStatus::Success, $result->getStatus());
    }
}
