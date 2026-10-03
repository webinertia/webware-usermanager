<?php

declare(strict_types=1);

namespace Webware\UserManager\CommandHandler;

use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\SetPasswordCommand;
use Webware\UserManager\Repository\UserRepositoryInterface;

final class SetPasswordHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function handle(SetPasswordCommand $command): CommandResult
    {
        $updated = $this->users->update($command->id, [
            'passwordHash'        => $command->passwordHash,
            'passwordSetRequired' => 0,
        ]);

        return new CommandResult($command, MessageStatus::Success, $updated);
    }
}
