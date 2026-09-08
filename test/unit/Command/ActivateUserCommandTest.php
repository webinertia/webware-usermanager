<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Command\ActivateUserCommand;

#[CoversClass(ActivateUserCommand::class)]
#[CoversMethod(ActivateUserCommand::class, '__construct')]
final class ActivateUserCommandTest extends TestCase
{
    #[Test]
    public function constructorAssignsId(): void
    {
        $command = new ActivateUserCommand(id: 42);

        static::assertSame(42, $command->id);
    }

    #[Test]
    public function getNameReturnsFullyQualifiedClassName(): void
    {
        $command = new ActivateUserCommand(id: 1);

        static::assertSame(ActivateUserCommand::class, $command->getName());
    }
}
