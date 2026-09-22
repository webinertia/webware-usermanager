<?php

declare(strict_types=1);

namespace Webware\UserManager\Command;

use Webware\Message\NotificationCapableInterface;
use Webware\MessageBus\Command\NamedCommandInterface;
use Webware\MessageBus\Command\NamedCommandTrait;

final class ToggleUserActiveCommand implements NamedCommandInterface, NotificationCapableInterface
{
    use NamedCommandTrait;

    public readonly string $successMessage;

    public readonly string $failureMessage;

    public function __construct(
        public readonly int $id,
    ) {
        $this->successMessage = 'User status updated.';
        $this->failureMessage = 'User status could not be updated. Please try again.';
    }
}
