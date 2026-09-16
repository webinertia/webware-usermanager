<?php

declare(strict_types=1);

namespace Webware\UserManager\CommandHandler;

use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\ToggleUserActiveCommand;
use Webware\UserManager\Repository\UserRepositoryInterface;

final class ToggleUserActiveHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function handle(ToggleUserActiveCommand $command): CommandResult
    {
        $user = $this->users->findById($command->id);

        if (null === $user) {
            return new CommandResult($command, MessageStatus::Failure, 'User not found.');
        }

        $this->users->update($command->id, [
            'active' => $user->active ? 0 : 1,
        ]);

        return new CommandResult($command, MessageStatus::Success, $this->users->findById($command->id));
    }
}
