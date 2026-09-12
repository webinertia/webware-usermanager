<?php

declare(strict_types=1);

namespace Webware\UserManager\Command;

use Webware\MessageBus\Command\NamedCommandInterface;
use Webware\MessageBus\Command\NamedCommandTrait;

/**
 * Activate a user and clear their verification token.
 */
final readonly class ActivateUserCommand implements NamedCommandInterface
{
    use NamedCommandTrait;

    public function __construct(
        public int $id,
    ) {
        $this->name = self::class;
    }
}
