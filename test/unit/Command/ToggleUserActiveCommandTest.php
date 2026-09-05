<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Command\ToggleUserActiveCommand;

#[CoversClass(ToggleUserActiveCommand::class)]
#[CoversMethod(ToggleUserActiveCommand::class, '__construct')]
final class ToggleUserActiveCommandTest extends TestCase
{
    #[Test]
    public function constructorAssignsId(): void
    {
        $command = new ToggleUserActiveCommand(id: 42);

        static::assertSame(42, $command->id);
    }

    #[Test]
    public function getNameReturnsFullyQualifiedClassName(): void
    {
        $command = new ToggleUserActiveCommand(id: 1);

        static::assertSame(ToggleUserActiveCommand::class, $command->getName());
    }
}
