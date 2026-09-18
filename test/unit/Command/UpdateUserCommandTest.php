<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Command\UpdateUserCommand;

#[CoversClass(UpdateUserCommand::class)]
#[CoversMethod(UpdateUserCommand::class, '__construct')]
final class UpdateUserCommandTest extends TestCase
{
    #[Test]
    public function constructorAssignsValues(): void
    {
        $command = new UpdateUserCommand(
            id       : '1',
            firstName: 'Jane',
            lastName : 'Doe',
            email    : 'jane@example.com',
            roleId   : 'Member',
            active   : true,
        );

        static::assertSame('1', $command->id);
        static::assertSame('Jane', $command->firstName);
        static::assertSame('Doe', $command->lastName);
        static::assertSame('jane@example.com', $command->email);
        static::assertSame('Member', $command->roleId);
        static::assertTrue($command->active);
    }

    #[Test]
    public function getCommandNameReturnsFullyQualifiedClassName(): void
    {
        $command = new UpdateUserCommand(
            id       : 1,
            firstName: 'Jane',
            lastName : 'Doe',
            email    : 'jane@example.com',
            roleId   : 'Member',
            active   : false,
        );

        static::assertSame(UpdateUserCommand::class, $command->getCommandName());
    }
}
