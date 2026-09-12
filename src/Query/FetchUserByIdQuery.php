<?php

declare(strict_types=1);

namespace Webware\UserManager\Query;

use Webware\MessageBus\Query\QueryInterface;

/**
 * Fetch a user by primary key.
 */
final readonly class FetchUserByIdQuery implements QueryInterface
{
    public function __construct(
        public int $id,
    ) {}
}
