<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\UpdateUserCommand;
use Webware\UserManager\CommandHandler\UpdateUserHandler;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Repository\UserRepositoryInterface;

#[CoversClass(UpdateUserHandler::class)]
#[CoversMethod(UpdateUserHandler::class, '__construct')]
#[CoversMethod(UpdateUserHandler::class, 'handle')]
final class UpdateUserHandlerTest extends TestCase
{
    #[Test]
    public function returnsFailureWhenUpdateThrows(): void
    {
        $user = new User(id: 1);

        $users = $this->createMock(UserRepositoryInterface::class);
        $users->method('findById')->willReturn($user);
        $users->expects($this->once())
            ->method('update')
            ->willThrowException(new RuntimeException('DB error'));

        $result = new UpdateUserHandler($users)->handle($this->command());

        static::assertSame(MessageStatus::Failure, $result->getStatus());
        static::assertSame('DB error', $result->getResult());
    }

    #[Test]
    public function returnsFailureWhenUserNotFound(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn(null);

        $result = new UpdateUserHandler($users)->handle($this->command(id: 999));

        static::assertSame(MessageStatus::Failure, $result->getStatus());
        static::assertSame('User not found.', $result->getResult());
    }

    #[Test]
    public function updatesUserAndReturnsSuccess(): void
    {
        $user = new User(id: 1);

        $users = $this->createMock(UserRepositoryInterface::class);
        $users->method('findById')->willReturn($user);
        $users->expects($this->once())
            ->method('update')
            ->with(1, [
                'firstName' => 'Jane',
                'lastName'  => 'Smith',
                'email'     => 'jane@example.com',
                'roleId'    => 'Member',
                'active'    => 1,
            ])
            ->willReturn(1);

        $result = new UpdateUserHandler($users)->handle($this->command());

        static::assertSame(MessageStatus::Success, $result->getStatus());
    }

    private function command(int $id = 1): UpdateUserCommand
    {
        return new UpdateUserCommand(
            id       : $id,
            firstName: 'Jane',
            lastName : 'Smith',
            email    : 'jane@example.com',
            roleId   : 'Member',
            active   : true,
        );
    }
}
