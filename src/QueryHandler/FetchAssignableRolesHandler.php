<?php

declare(strict_types=1);

namespace Webware\UserManager\QueryHandler;

use Webware\Core\AclInterface;
use Webware\Core\Role;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\MessageBus\QueryHandlerInterface;
use Webware\UserManager\Query\FetchAssignableRolesQuery;

use function array_intersect_key;
use function array_key_exists;
use function array_keys;

final readonly class FetchAssignableRolesHandler implements QueryHandlerInterface
{
    public function __construct(
        private AclInterface $acl,
    ) {}

    public function handle(FetchAssignableRolesQuery $query): QueryResult
    {
        return new QueryResult(
            query : $query,
            status: MessageStatus::Success,
            result: $this->assignableRoles(actorRoleId: $query->actorRoleId),
        );
    }

    /**
     * The roles $actorRoleId may assign: its own role plus every role it inherits,
     * minus the guest role.
     *
     * An actor role that is not in the registry may assign nothing: nothing it can reach
     * is a registry key, so the intersection below is empty.
     *
     * @return list<string>
     */
    private function assignableRoles(string $actorRoleId): array
    {
        $registry = $this->acl->getRoles();

        $lineage = $this->lineage(
            roleId  : $actorRoleId,
            registry: $registry,
        );
        unset($lineage[Role::Guest->value]);

        return array_keys(array_intersect_key($registry, $lineage));
    }

    /**
     * $roleId and every role reachable through its parent lists.
     *
     * The visited set terminates the walk on a cyclic registry and keeps a role
     * with several parents from being walked more than once.
     *
     * @param array<string, string[]> $registry
     * @param array<string, true>     $visited
     *
     * @return array<string, true>
     */
    private function lineage(string $roleId, array $registry, array $visited = []): array
    {
        $visited[$roleId] = true;

        foreach ($registry[$roleId] ?? [] as $parentRoleId) {
            if (array_key_exists($parentRoleId, $visited)) {
                continue;
            }

            $visited = $this->lineage(
                roleId  : $parentRoleId,
                registry: $registry,
                visited : $visited,
            );
        }

        return $visited;
    }
}
