<?php

declare(strict_types=1);

namespace Webware\UserManager\Command;

use SensitiveParameter;
use Webware\MessageBus\Command\NamedCommandInterface;
use Webware\MessageBus\Command\NamedCommandTrait;

/**
 * Regenerate a user's verification token.
 */
final class RegenerateVerificationTokenCommand implements NamedCommandInterface
{
    use NamedCommandTrait;

    public function __construct(
        public readonly int $id,
        #[SensitiveParameter]
        public readonly string $token,
        public readonly string $tokenCreatedAt,
    ) {}
}
