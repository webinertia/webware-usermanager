<?php

declare(strict_types=1);

namespace Webware\UserManager\CommandHandler;

use DateTimeImmutable;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\Core\UserInterface;
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
        $result = $this->users->save([
            'firstName'           => $command->firstName,
            'lastName'            => $command->lastName,
            'passwordHash'        => $command->passwordHash,
            'email'               => $command->email,
            'roleId'              => $command->roleId,
            'verificationToken'   => $command->verificationToken,
            'active'              => $command->active,
            'passwordSetRequired' => $command->passwordSetRequired,
            'tokenCreatedAt'      => $this->formatTimestamp($command->tokenCreatedAt),
            'createdAt'           => $this->formatTimestamp($command->createdAt),
        ]);

        if ($result) {
            if (! $command->active) {
                $this->eventDispatcher->dispatch(new SendVerificationEmailEvent($command));
            }

            return new CommandResult($command, MessageStatus::Success, $result);
        }

        return new CommandResult($command, MessageStatus::Failure, 'Failed to save user.');
    }

    /**
     * The command accepts either form but only ever stores the formatted string, and its default
     * is still a DateTimeImmutable, so the payload is normalized here before it reaches the repository.
     */
    private function formatTimestamp(DateTimeImmutable|string $value): string
    {
        return $value instanceof DateTimeImmutable
            ? $value->format(UserInterface::DATETIME_FORMAT)
            : $value;
    }
}
