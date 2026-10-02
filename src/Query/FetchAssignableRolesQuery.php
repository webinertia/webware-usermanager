<?php

declare(strict_types=1);

namespace Webware\UserManager\Query;

use Webware\MessageBus\Query\QueryInterface;

/**
 * Fetch the role IDs the given actor role may assign.
 */
final readonly class FetchAssignableRolesQuery implements QueryInterface
{
    public function __construct(
        public string $actorRoleId,
    ) {}
}
