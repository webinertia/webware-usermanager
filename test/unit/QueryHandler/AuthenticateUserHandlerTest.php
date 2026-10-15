<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\QueryHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\Log\Event\LogEvent;
use Webware\Log\LogChannel;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Auth\AuthenticationResult;
use Webware\UserManager\Auth\AuthenticationStatus;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Query\AuthenticateUserQuery;
use Webware\UserManager\QueryHandler\AuthenticateUserHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

use function bin2hex;
use function random_bytes;

#[CoversClass(AuthenticateUserHandler::class)]
#[CoversMethod(AuthenticateUserHandler::class, '__construct')]
#[CoversMethod(AuthenticateUserHandler::class, 'handle')]
final class AuthenticateUserHandlerTest extends TestCase
{
    #[Test]
    public function activeUserLogsAndReturnsTheUser(): void
    {
        $user = new User(
            id       : 1,
            firstName: 'Jane',
            lastName : 'Doe',
            email    : 'jane@example.com',
            active   : true,
        );

        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('authenticate')->willReturn($user);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(static::callback(
                static fn(LogEvent $event): bool => (
                    LogChannel::Security === $event->getChannel()
                    && 'Jane Doe authenticated successfully.' === $event->getMessage()
                ),
            ));

        $result = new AuthenticateUserHandler($users, $dispatcher)->handle(
            new AuthenticateUserQuery(
                credential: 'jane@example.com',
                password  : bin2hex(random_bytes(16)),
            ),
        );

        /** @var AuthenticationResult $auth */
        $auth = $result->getResult();

        self::assertSame(MessageStatus::Success, $result->getStatus());
        self::assertSame(AuthenticationStatus::Success, $auth->status);
        self::assertSame($user, $auth->user);
    }

    #[Test]
    public function inactiveUserLogsAndReportsNotActive(): void
    {
        $user = new User(
            id    : 1,
            email : 'jane@example.com',
            active: false,
        );

        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('authenticate')->willReturn($user);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(static::callback(
                static fn(LogEvent $event): bool => (
                    LogChannel::Security === $event->getChannel()
                    && 'Failed login attempt for inactive user: jane@example.com' === $event->getMessage()
                ),
            ));

        $result = new AuthenticateUserHandler($users, $dispatcher)->handle(
            new AuthenticateUserQuery(
                credential: 'jane@example.com',
                password  : bin2hex(random_bytes(16)),
            ),
        );

        /** @var AuthenticationResult $auth */
        $auth = $result->getResult();

        self::assertSame(MessageStatus::Failure, $result->getStatus());
        self::assertSame(AuthenticationStatus::NotActive, $auth->status);
        self::assertNull($auth->user);
    }

    #[Test]
    public function invalidCredentialsLogsAndReportsInvalidCredentials(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('authenticate')->willReturn(null);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(static::callback(
                static fn(LogEvent $event): bool => (
                    LogChannel::Security === $event->getChannel()
                    && 'Failed login attempt.' === $event->getMessage()
                    && ['credential' => 'jane@example.com'] === $event->getContext()
                ),
            ));

        $result = new AuthenticateUserHandler($users, $dispatcher)->handle(
            new AuthenticateUserQuery(
                credential: 'jane@example.com',
                password  : bin2hex(random_bytes(16)),
            ),
        );

        /** @var AuthenticationResult $auth */
        $auth = $result->getResult();

        self::assertSame(MessageStatus::Failure, $result->getStatus());
        self::assertSame(AuthenticationStatus::InvalidCredentials, $auth->status);
        self::assertNull($auth->user);
    }
}
