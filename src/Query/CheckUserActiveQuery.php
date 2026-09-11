<?php

declare(strict_types=1);

namespace Webware\UserManager\Query;

use Webware\MessageBus\Query\QueryInterface;

/**
 * Check whether a user is active.
 */
final readonly class CheckUserActiveQuery implements QueryInterface
{
    public function __construct(
        public int $id,
    ) {}
}
