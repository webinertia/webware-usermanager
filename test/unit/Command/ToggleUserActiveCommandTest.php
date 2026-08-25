<?php

declare(strict_types=1);

/**
 * This file is part of the Webware UserManager package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
