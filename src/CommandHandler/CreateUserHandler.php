<?php

declare(strict_types=1);

namespace Webware\UserManager\CommandHandler;

use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\CreateUserCommand;
use Webware\UserManager\Event\SendVerificationEmailEvent;
use Webware\UserManager\Repository\UserRepositoryInterface;

final class CreateUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function handle(CreateUserCommand $command): CommandResult
    {
        if ($result = $this->users->save($command)) {
            $this->eventDispatcher->dispatch(new SendVerificationEmailEvent($command));
            return new CommandResult($command, MessageStatus::Success, $result);
        }

        return new CommandResult($command, MessageStatus::Failure, 'Failed to save user.');
    }
}
