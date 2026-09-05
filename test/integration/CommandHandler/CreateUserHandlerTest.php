<?php

declare(strict_types=1);

namespace WebwareTestIntegration\UserManager\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\CreateUserCommand;
use Webware\UserManager\CommandHandler\CreateUserHandler;
use Webware\UserManager\Event\SendVerificationEmailEvent;
use Webware\UserManager\Repository\UserRepositoryInterface;

use function bin2hex;
use function random_bytes;

#[CoversClass(CreateUserHandler::class)]
#[CoversMethod(CreateUserHandler::class, '__construct')]
#[CoversMethod(CreateUserHandler::class, 'handle')]
final class CreateUserHandlerTest extends TestCase
{
    /** @var EventDispatcherInterface&MockObject */
    private EventDispatcherInterface&MockObject $dispatcher;

    #[Test]
    public function successfulSaveDispatchesVerificationEmailEvent(): void
    {
        $command = $this->createCommand();

        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('save')->willReturn(1);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                static::callback(
                    static fn(object $event): bool => (
                        $event instanceof SendVerificationEmailEvent
                        && $event->getTarget() === $command
                    ),
                ),
            );

        $handler = new CreateUserHandler($users, $this->dispatcher);

        $result = $handler->handle($command);

        static::assertInstanceOf(CommandResultInterface::class, $result);
        static::assertSame(MessageStatus::Success, $result->getStatus());
        static::assertSame(1, $result->getResult());
    }

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    private function createCommand(): CreateUserCommand
    {
        return new CreateUserCommand(
            firstName        : 'Jane',
            lastName         : 'Doe',
            passwordHash     : bin2hex(random_bytes(16)),
            email            : 'JANE@EXAMPLE.COM',
            roleId           : ['member'],
            verificationToken: bin2hex(random_bytes(16)),
        );
    }
}
