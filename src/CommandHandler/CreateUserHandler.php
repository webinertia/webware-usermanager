<?php

declare(strict_types=1);

namespace Webware\UserManager\CommandHandler;

use DateTimeImmutable;
use Psr\EventDispatcher\EventDispatcherInterface;
use Ramsey\Uuid\Uuid;
use Throwable;
use Webware\MessageBus\Command\CommandInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\CreateUserCommand;
use Webware\UserManager\Event\SendVerificationEmailEvent;
use Webware\UserManager\Repository\UserRepositoryInterface;

use function assert;
use function json_encode;
use function password_hash;

final class CreateUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function handle(CommandInterface $command): CommandResultInterface
    {
        assert($command instanceof CreateUserCommand);

        if ($result = $this->users->save($command)) {
            $this->eventDispatcher->dispatch(new SendVerificationEmailEvent($command));
            return new CommandResult($command, MessageStatus::Success, $result);
        }

        return new CommandResult($command, MessageStatus::Failure, 'Failed to save user.');
    }
}
