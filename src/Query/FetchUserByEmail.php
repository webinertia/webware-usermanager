<?php

declare(strict_types=1);

namespace Webware\UserManager\Query;

use Webware\MessageBus\Query\QueryInterface;

/**
 * Fetch a user by email address.
 */
final readonly class FetchUserByEmail implements QueryInterface
{
    public function __construct(
        public string $email,
    ) {}
}
