<?php

declare(strict_types=1);

namespace Webware\UserManager\CommandHandler;

use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\RegenerateVerificationTokenCommand;
use Webware\UserManager\Repository\UserRepositoryInterface;

final class RegenerateVerificationTokenHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function handle(RegenerateVerificationTokenCommand $command): CommandResult
    {
        $updated = $this->users->update($command->id, [
            'verificationToken' => $command->token,
            'tokenCreatedAt'    => $command->tokenCreatedAt,
        ]);

        return new CommandResult($command, MessageStatus::Success, $updated);
    }
}
