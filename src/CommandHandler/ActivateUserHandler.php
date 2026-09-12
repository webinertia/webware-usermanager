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
use Webware\UserManager\Command\ActivateUserCommand;
use Webware\UserManager\Repository\UserRepositoryInterface;

final class ActivateUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @throws PslTypeException
     */
    public function handle(CommandInterface $command): CommandResultInterface
    {
        Type\instance_of(ActivateUserCommand::class)->assert($command);

        $updated = $this->users->update($command->id, [
            'active'            => 1,
            'verificationToken' => null,
            'tokenCreatedAt'    => null,
        ]);

        return new CommandResult($command, MessageStatus::Success, $updated);
    }
}
