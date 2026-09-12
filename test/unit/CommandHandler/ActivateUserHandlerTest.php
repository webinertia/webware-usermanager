<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\ActivateUserCommand;
use Webware\UserManager\CommandHandler\ActivateUserHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

#[CoversClass(ActivateUserHandler::class)]
#[CoversMethod(ActivateUserHandler::class, '__construct')]
#[CoversMethod(ActivateUserHandler::class, 'handle')]
final class ActivateUserHandlerTest extends TestCase
{
    #[Test]
    public function activatesUserAndClearsVerificationToken(): void
    {
        $users = $this->createMock(UserRepositoryInterface::class);
        $users->expects($this->once())
            ->method('update')
            ->with(7, ['active' => 1, 'verificationToken' => null, 'tokenCreatedAt' => null])
            ->willReturn(1);

        $result = new ActivateUserHandler($users)->handle(new ActivateUserCommand(id: 7));

        static::assertSame(MessageStatus::Success, $result->getStatus());
        static::assertSame(1, $result->getResult());
    }
}
