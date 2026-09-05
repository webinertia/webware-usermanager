<?php

declare(strict_types=1);

namespace Webware\UserManager\CommandHandler;

use Throwable;
use Webware\MessageBus\Command\CommandInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\UpdateUserCommand;
use Webware\UserManager\Repository\UserRepositoryInterface;

use function assert;
use function json_encode;

final class UpdateUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function handle(CommandInterface $command): CommandResultInterface
    {
        assert($command instanceof UpdateUserCommand);

        $user = $this->users->findById($command->id);

        if ($user === null) {
            return new CommandResult($command, MessageStatus::Failure, 'User not found.');
        }

        try {
            $this->users->update($command->id, [
                'firstName' => $command->firstName,
                'lastName'  => $command->lastName,
                'email'     => $command->email,
                'roleId'    => json_encode($command->roleId),
                'active'    => $command->active ? 1 : 0,
            ]);
        } catch (Throwable $e) {
            return new CommandResult($command, MessageStatus::Failure, $e->getMessage());
        }

        return new CommandResult($command, MessageStatus::Success, $this->users->findById($command->id));
    }
}
