<?php

declare(strict_types=1);

namespace Webware\UserManager\Command;

use DateTimeImmutable;
use SensitiveParameter;
use Webware\Core\UserInterface;
use Webware\MessageBus\Command\NamedCommandInterface;
use Webware\MessageBus\Command\NamedCommandTrait;

use function password_get_info;
use function password_hash;
use function strtolower;

final class CreateUserCommand implements NamedCommandInterface
{
    use NamedCommandTrait;

    // @mago-expect lint:excessive-parameter-list - accepted: the promoted properties are the row shape; splitting the list changes every call site.
    public function __construct(
        public private(set) string $firstName {
            get => $this->firstName;
            set(string $value) {
                $this->firstName = $value;
            }
        },
        public private(set) string $lastName {
            get => $this->lastName;
            set(string $value) {
                $this->lastName = $value;
            }
        },
        #[SensitiveParameter]
        public private(set) string $passwordHash {
            get => $this->passwordHash;
            set(string $value) {
                // @mago-expect analysis:redundant-comparison - accepted: password_get_info()['algo'] is null for a non-hash on PHP 8.4, so this test is what routes plaintext into password_hash(); mago types the element as non-null.
                if (null === password_get_info($value)['algo']) {
                    $this->passwordHash = password_hash($value, PASSWORD_DEFAULT);
                } else {
                    $this->passwordHash = $value;
                }
            }
        },
        public private(set) string $email {
            get => $this->email;
            set(string $value) {
                $this->email = strtolower($value);
            }
        },
        public private(set) string $roleId,
        #[SensitiveParameter]
        public private(set) string $verificationToken {
            get => $this->verificationToken;
            set(string $value) {
                $this->verificationToken = $value;
            }
        },
        public private(set) bool $active = false {
            get => $this->active;
            set(bool $value) {
                $this->active = $value;
            }
        },
        public private(set) DateTimeImmutable|string $tokenCreatedAt = new DateTimeImmutable() {
            get => $this->tokenCreatedAt;
            set(DateTimeImmutable|string $value) {
                if ($value instanceof DateTimeImmutable) {
                    $this->tokenCreatedAt = $value->format(UserInterface::DATETIME_FORMAT);
                } else {
                    $this->tokenCreatedAt = new DateTimeImmutable($value)->format(UserInterface::DATETIME_FORMAT);
                }
            }
        },
        public private(set) DateTimeImmutable|string $createdAt = new DateTimeImmutable() {
            get => $this->createdAt;
            set(DateTimeImmutable|string $value) {
                if ($value instanceof DateTimeImmutable) {
                    $this->createdAt = $value->format(UserInterface::DATETIME_FORMAT);
                } else {
                    $this->createdAt = new DateTimeImmutable($value)->format(UserInterface::DATETIME_FORMAT);
                }
            }
        },
    ) {}
}
