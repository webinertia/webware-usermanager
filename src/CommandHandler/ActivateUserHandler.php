<?php

declare(strict_types=1);

namespace Webware\UserManager\CommandHandler;

use Webware\MessageBus\Command\CommandInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\ActivateUserCommand;
use Webware\UserManager\Repository\UserRepositoryInterface;

use function assert;

final class ActivateUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function handle(CommandInterface $command): CommandResultInterface
    {
        assert($command instanceof ActivateUserCommand, description: 'Expected an ActivateUserCommand');

        $updated = $this->users->update($command->id, [
            'active'            => 1,
            'verificationToken' => null,
            'tokenCreatedAt'    => null,
        ]);

        return new CommandResult($command, MessageStatus::Success, $updated);
    }
}
