<?php

declare(strict_types=1);

namespace Webware\UserManager\Command;

use SensitiveParameter;
use Webware\MessageBus\Command\NamedCommandInterface;
use Webware\MessageBus\Command\NamedCommandTrait;

/**
 * Regenerate a user's verification token.
 */
final readonly class RegenerateVerificationTokenCommand implements NamedCommandInterface
{
    use NamedCommandTrait;

    public function __construct(
        public int $id,
        #[SensitiveParameter]
        public string $token,
        public string $tokenCreatedAt,
    ) {
        $this->name = self::class;
    }
}
