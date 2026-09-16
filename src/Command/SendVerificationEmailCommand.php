<?php

declare(strict_types=1);

namespace Webware\UserManager\Command;

use Webware\MessageBus\Command\NamedCommandInterface;
use Webware\MessageBus\Command\NamedCommandTrait;

/**
 * Carries everything the verification email needs, so the handler that renders
 * and sends it never has to reach back for request or view state.
 */
final class SendVerificationEmailCommand implements NamedCommandInterface
{
    use NamedCommandTrait;

    public function __construct(
        public private(set) string $to,
        public private(set) string $firstName,
        public private(set) string $verificationUrl,
        public private(set) string $subject,
    ) {
        $this->name = self::class;
    }
}
