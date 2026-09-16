<?php

declare(strict_types=1);

namespace Webware\UserManager\Event;

use Override;
use Webware\Event\Event;
use Webware\UserManager\Command\CreateUserCommand;

final class SendVerificationEmailEvent extends Event
{
    public function __construct(
        public readonly CreateUserCommand $target,
    ) {}

    public function getEmail(): string
    {
        return $this->target->email;
    }

    #[Override]
    public function getTarget(): CreateUserCommand
    {
        return $this->target;
    }

    public function getToken(): string
    {
        return $this->target->verificationToken;
    }
}
