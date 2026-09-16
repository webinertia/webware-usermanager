<?php

declare(strict_types=1);

namespace Webware\UserManager\Command;

use Webware\MessageBus\Command\NamedCommandInterface;
use Webware\MessageBus\Command\NamedCommandTrait;

/**
 * Activate a user and clear their verification token.
 */
final class ActivateUserCommand implements NamedCommandInterface
{
    use NamedCommandTrait;

    public function __construct(
        public readonly int $id,
    ) {}
}
