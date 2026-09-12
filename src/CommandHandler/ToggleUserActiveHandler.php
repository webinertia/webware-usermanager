<?php

declare(strict_types=1);

namespace Webware\UserManager\CommandHandler;

use Psl\Type;
use Psl\Type\Exception\ExceptionInterface as PslTypeException;
use Webware\MessageBus\Command\CommandInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\ToggleUserActiveCommand;
use Webware\UserManager\Repository\UserRepositoryInterface;

final class ToggleUserActiveHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @throws PslTypeException
     */
    public function handle(CommandInterface $command): CommandResultInterface
    {
        Type\instance_of(ToggleUserActiveCommand::class)->assert($command);

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
