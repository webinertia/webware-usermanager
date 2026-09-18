<?php

declare(strict_types=1);

namespace Webware\UserManager\Entity;

use DateTimeImmutable;
use DateTimeZone;
use Override;
use PhpDb\ResultSet\RowPrototypeInterface;
use SensitiveParameter;
use Webware\Core\Role;
use Webware\Core\UserInterface;
use Webware\UserManager\Exception\UnassignedIdentityException;

use function is_array;
use function is_string;
use function json_decode;
use function json_validate;
use function password_get_info;
use function password_hash;
use function strtolower;

use const PASSWORD_DEFAULT;

/**
 * @import-type UserPrototype from UserInterface
 */
// @mago-expect lint:cyclomatic-complexity,too-many-methods - accepted: the property hooks and builders are deliberately kept on one entity.
// @mago-expect analysis:class-must-be-final,unsafe-instantiation - accepted: User is non-final by design so consumers can extend it, and the with*() builders and populate() must construct `static`.
class User implements UserInterface
{
    // @mago-expect lint:excessive-parameter-list - accepted: the promoted properties are the row shape.
    public function __construct(
        public private(set) int|string|null $id = null {
            get => $this->id ?? null;
            set(int|string|null $value) {
                if (null === $value) {
                    $this->id = null;
                } else {
                    $this->id = is_string($value) ? (int) $value : $value;
                }
            }
        },
        public private(set) string $roleId = Role::Guest->value,
        public private(set) ?string $firstName = null,
        public private(set) ?string $lastName = null,
        public private(set) ?string $email = null {
            get => $this->email;
            set(?string $value) {
                $this->email = null !== $value ? strtolower($value) : null;
            }
        },
        #[SensitiveParameter] public private(set) ?string $passwordHash = null,
        public private(set) int|bool|null $active = null {
            get => $this->active ?? false;
            set(int|bool|null $value) {
                $this->active = (bool) $value;
            }
        },
        /** @var DateTimeImmutable|array<array-key, mixed>|string|null */
        public private(set) DateTimeImmutable|array|string|null $createdAt = null {
            get => $this->createdAt ?? new DateTimeImmutable();
            set(DateTimeImmutable|array|string|null $value) {
                if (is_array($value) && isset($value['date'])) {
                    $this->createdAt = new DateTimeImmutable($value['date'], new DateTimeZone($value['timezone']));
                } elseif (is_string($value)) {
                    $this->createdAt = new DateTimeImmutable($value);
                } else {
                    $this->createdAt = $value;
                }
            }
        },
        #[SensitiveParameter] public private(set) ?string $verificationToken = null,

        /** @var DateTimeImmutable|array<array-key, mixed>|string|null */
        public private(set) DateTimeImmutable|array|string|null $tokenCreatedAt = null {
            get => $this->tokenCreatedAt ?? new DateTimeImmutable();
            set(DateTimeImmutable|array|string|null $value) {
                if (is_array($value) && isset($value['date'])) {
                    $this->tokenCreatedAt = DateTimeImmutable::createFromFormat(
                        self::DATETIME_FORMAT,
                        $value['date'],
                        new DateTimeZone($value['timezone'] ?? 'UTC'),
                    );
                } elseif (is_string($value)) {
                    $this->tokenCreatedAt = new DateTimeImmutable($value);
                } else {
                    $this->tokenCreatedAt = $value;
                }
            }
        },
        /** @var array<string, mixed>|null */
        public private(set) array|string|null $details = null {
            get => $this->details ?? [];
            set(array|string|null $value) {
                if (is_string($value)) {
                    if (json_validate($value)) {
                        $decoded       = json_decode($value, associative: true);
                        $this->details = is_array($decoded) ? $decoded : [];
                    } else {
                        $this->details = [$value];
                    }
                } else {
                    $this->details = $value;
                }
            }
        },
    ) {}

    /** @param mixed $default */
    #[Override]
    public function getDetail(string $name, $default = null): mixed
    {
        return $this->details[$name] ?? $default;
    }

    /** @return array<string, mixed> */
    #[Override]
    public function getDetails(): array
    {
        return $this->details ?? [];
    }

    /**
     * A hydrated user reports its email address. A guest principal is constructed without
     * an email address, so it reports the guest role name instead.
     *
     * @throws UnassignedIdentityException when a non-guest entity carries no email address.
     */
    #[Override]
    public function getIdentity(): string
    {
        if (null !== $this->email) {
            return $this->email;
        }

        return Role::Guest->value === $this->getRoleId()
            ? Role::Guest->value
            : throw new UnassignedIdentityException(
                'Cannot resolve a user identity: the user row has no email address.',
            );
    }

    /**
     * Implements ProprietaryInterface — used by the Laminas Ownership assertion.
     * Returns the user's primary key so the assertion can compare
     * $role->getOwnerId() === $resource->getOwnerId().
     */
    #[Override]
    public function getOwnerId(): ?int
    {
        return $this->id;
    }

    /**
     * Implements ResourceInterface — identifies this object as the 'user' ACL resource.
     * Allows $acl->isAllowed($role, $userEntity, $privilege) calls.
     */
    #[Override]
    public function getResourceId(): string
    {
        return 'user';
    }

    #[Override]
    public function getRoleId(): string
    {
        return $this->roleId;
    }

    /** @return iterable<int|string, string> */
    #[Override]
    public function getRoles(): iterable
    {
        return [$this->roleId];
    }

    /** @param array<array-key, mixed> $data */
    #[Override]
    public function populate(array $data): UserInterface&RowPrototypeInterface
    {
        return new static(...$data);
    }

    /**
     * @return UserPrototype
     */
    #[Override]
    public function toArray(): array
    {
        /** @var UserPrototype */
        return (array) $this;
    }

    #[Override]
    public function withActive(bool $active): static
    {
        return new static(
            id               : $this->id,
            roleId           : $this->roleId,
            firstName        : $this->firstName,
            lastName         : $this->lastName,
            email            : $this->email,
            passwordHash     : $this->passwordHash,
            active           : $active,
            createdAt        : $this->createdAt,
            verificationToken: $this->verificationToken,
            tokenCreatedAt   : $this->tokenCreatedAt,
            details          : $this->details,
        );
    }

    #[Override]
    public function withDetail(string $name, mixed $value): static
    {
        $details = is_array($this->details) ? $this->details : [];

        return new static(
            id               : $this->id,
            roleId           : $this->roleId,
            firstName        : $this->firstName,
            lastName         : $this->lastName,
            email            : $this->email,
            passwordHash     : $this->passwordHash,
            active           : $this->active,
            createdAt        : $this->createdAt,
            verificationToken: $this->verificationToken,
            tokenCreatedAt   : $this->tokenCreatedAt,
            details          : [...$details, $name => $value],
        );
    }

    #[Override]
    public function withEmail(string $email): static
    {
        return new static(
            id               : $this->id,
            roleId           : $this->roleId,
            firstName        : $this->firstName,
            lastName         : $this->lastName,
            email            : $email,
            passwordHash     : $this->passwordHash,
            active           : $this->active,
            createdAt        : $this->createdAt,
            verificationToken: $this->verificationToken,
            tokenCreatedAt   : $this->tokenCreatedAt,
            details          : $this->details,
        );
    }

    #[Override]
    public function withFirstName(string $firstName): static
    {
        return new static(
            id               : $this->id,
            roleId           : $this->roleId,
            firstName        : $firstName,
            lastName         : $this->lastName,
            email            : $this->email,
            passwordHash     : $this->passwordHash,
            active           : $this->active,
            createdAt        : $this->createdAt,
            verificationToken: $this->verificationToken,
            tokenCreatedAt   : $this->tokenCreatedAt,
            details          : $this->details,
        );
    }

    /**
     * In this implementation the identity is the email address, so the builder delegates
     * to withEmail() and keeps its normalisation.
     */
    #[Override]
    public function withIdentity(string $identity): static
    {
        return $this->withEmail($identity);
    }

    #[Override]
    public function withLastName(string $lastName): static
    {
        return new static(
            id               : $this->id,
            roleId           : $this->roleId,
            firstName        : $this->firstName,
            lastName         : $lastName,
            email            : $this->email,
            passwordHash     : $this->passwordHash,
            active           : $this->active,
            createdAt        : $this->createdAt,
            verificationToken: $this->verificationToken,
            tokenCreatedAt   : $this->tokenCreatedAt,
            details          : $this->details,
        );
    }

    #[Override]
    public function withPasswordHash(string $passwordHash): static
    {
        // @mago-expect analysis:redundant-comparison - accepted: password_get_info()['algo'] is null for a non-hash on PHP 8.4, so this test is what routes plaintext into password_hash(); mago types the element as non-null.
        if (null === password_get_info($passwordHash)['algo']) {
            $passwordHash = password_hash($passwordHash, PASSWORD_DEFAULT);
        }

        return new static(
            id               : $this->id,
            roleId           : $this->roleId,
            firstName        : $this->firstName,
            lastName         : $this->lastName,
            email            : $this->email,
            passwordHash     : $passwordHash,
            active           : $this->active,
            createdAt        : $this->createdAt,
            verificationToken: $this->verificationToken,
            tokenCreatedAt   : $this->tokenCreatedAt,
            details          : $this->details,
        );
    }

    #[Override]
    public function withRoleId(string $roleId): static
    {
        return new static(
            id               : $this->id,
            roleId           : $roleId,
            firstName        : $this->firstName,
            lastName         : $this->lastName,
            email            : $this->email,
            passwordHash     : $this->passwordHash,
            active           : $this->active,
            createdAt        : $this->createdAt,
            verificationToken: $this->verificationToken,
            tokenCreatedAt   : $this->tokenCreatedAt,
            details          : $this->details,
        );
    }

    /** @param array<string, mixed>|null $withRowData */
    public function __invoke(?array $withRowData = null): UserInterface
    {
        if (null !== $withRowData) {
            return $this->populate(data: $withRowData);
        }

        return new static();
    }
}
