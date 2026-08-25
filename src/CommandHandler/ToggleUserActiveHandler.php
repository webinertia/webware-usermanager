<?php

declare(strict_types=1);

/**
 * This file is part of the Webware Farmers Store Inventory package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webware\UserManager\CommandHandler;

use Webware\MessageBus\Command\CommandInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\ToggleUserActiveCommand;
use Webware\UserManager\Repository\UserRepositoryInterface;

use function assert;

final class ToggleUserActiveHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function handle(CommandInterface $command): CommandResultInterface
    {
        assert($command instanceof ToggleUserActiveCommand);

        $user = $this->users->findById($command->id);

        if ($user === null) {
            return new CommandResult($command, MessageStatus::Failure, 'User not found.');
        }

        $this->users->update($command->id, [
            'active' => $user->active ? 0 : 1,
        ]);

        return new CommandResult($command, MessageStatus::Success, $this->users->findById($command->id));
    }
}
