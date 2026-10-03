<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\CreateUserCommand;
use Webware\UserManager\Http\Admin\CreateUserState;

use function bin2hex;
use function random_bytes;

#[CoversClass(CreateUserState::class)]
#[CoversMethod(CreateUserState::class, '__construct')]
final class CreateUserStateTest extends TestCase
{
    #[Test]
    public function carriesWhatTheMiddlewareCollected(): void
    {
        $command = new CreateUserCommand(
            firstName        : 'Jane',
            lastName         : 'Doe',
            passwordHash     : bin2hex(random_bytes(16)),
            email            : 'jane@example.com',
            roleId           : 'Member',
            verificationToken: bin2hex(random_bytes(16)),
        );

        $result = new CommandResult($command, MessageStatus::Success, 7);

        $state = new CreateUserState(
            assignableRoles: ['Member', 'Administrator'],
            errors         : ['roleId' => ['The selected role is not one you may assign.']],
            old            : ['email' => 'jane@example.com'],
            result         : $result,
        );

        self::assertSame(['Member', 'Administrator'], $state->assignableRoles);
        self::assertSame(['roleId' => ['The selected role is not one you may assign.']], $state->errors);
        self::assertSame(['email' => 'jane@example.com'], $state->old);
        self::assertSame($result, $state->result);
    }

    #[Test]
    public function defaultsToAnEmptyState(): void
    {
        $state = new CreateUserState();

        self::assertSame([], $state->assignableRoles);
        self::assertSame([], $state->errors);
        self::assertSame([], $state->old);
        self::assertNull($state->result);
    }
}
