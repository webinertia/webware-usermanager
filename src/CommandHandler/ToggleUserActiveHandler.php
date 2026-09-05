<?php

declare(strict_types=1);

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
