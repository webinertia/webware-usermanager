<?php

declare(strict_types=1);

namespace Webware\UserManager\Command;

use SensitiveParameter;
use Webware\MessageBus\Command\NamedCommandInterface;
use Webware\MessageBus\Command\NamedCommandTrait;

use function password_hash;

use const PASSWORD_DEFAULT;

/**
 * Sets the password of an account that was created without a usable one.
 *
 * The account is activated separately, by {@see ActivateUserCommand}.
 */
final class SetPasswordCommand implements NamedCommandInterface
{
    use NamedCommandTrait;

    /**
     * @param string $passwordHash the plaintext password; hashed on assignment
     */
    public function __construct(
        public readonly int $id,
        #[SensitiveParameter]
        public private(set) string $passwordHash {
            get => $this->passwordHash;
            set(string $value) {
                $this->passwordHash = password_hash($value, PASSWORD_DEFAULT);
            }
        },
    ) {}
}
