<?php

declare(strict_types=1);

namespace Webware\UserManager;

use DateTimeImmutable;
use Webware\Core\UserInterface as CoreUserInterface;

/**
 * The user fields this component persists and reads.
 *
 * Core's contract mirrors Mezzio's and declares no properties, so the identity fields that
 * belong to the table this package owns are declared here instead. Types are those of
 * {@see Entity\User}, which satisfies this contract.
 *
 * @api
 */
interface UserInterface extends CoreUserInterface
{
    public int|string|null $id { get; }

    public int|bool|null $active { get; }

    public ?string $firstName { get; }

    public ?string $lastName { get; }

    public ?string $passwordHash { get; }

    /** @var DateTimeImmutable|array<array-key, mixed>|string|null */
    public DateTimeImmutable|array|string|null $tokenCreatedAt { get; }
}
