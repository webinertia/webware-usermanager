<?php

declare(strict_types=1);

namespace Webware\UserManager\Command;

use Webware\MessageBus\Command\NamedCommandInterface;
use Webware\MessageBus\Command\NamedCommandTrait;

/**
 * Carries everything the resend variant of the verification email needs. It is
 * a separate command from the registration send because the copy differs, and a
 * command may only ever have one handler.
 */
final class ResendVerificationEmailCommand implements NamedCommandInterface
{
    use NamedCommandTrait;

    public function __construct(
        public private(set) string $to,
        public private(set) string $toName,
        public private(set) string $firstName,
        public private(set) string $verificationUrl,
        public private(set) string $subject,
    ) {
        $this->name = self::class;
    }
}
