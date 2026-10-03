<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Admin;

use Psl\Type;
use Psl\Type\Exception\AssertException;
use Psr\Http\Message\ServerRequestInterface;
use Webware\Core\UserInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\Query\QueryResult;
use Webware\UserManager\Query\FetchAssignableRolesQuery;

use function array_shift;

/**
 * The roles the acting administrator may assign, resolved once per request.
 *
 * An actor that is missing or carries no role gets an empty list, which fails
 * closed: nothing can be assigned and any submitted role is rejected.
 */
final readonly class AssignableRolesProvider
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {}

    /**
     * @throws AssertException
     *
     * @return list<string>
     */
    public function forRequest(ServerRequestInterface $request): array
    {
        $actorRoleId = $this->actorRoleId($request);

        if (null === $actorRoleId) {
            return [];
        }

        /** @var QueryResult $result */
        $result = $this->messageBus->handle(
            new FetchAssignableRolesQuery(actorRoleId: $actorRoleId),
        );

        return Type\vec(Type\string())->assert($result->getResult());
    }

    /**
     * The actor's role, read through the component's own user contract.
     */
    private function actorRoleId(ServerRequestInterface $request): ?string
    {
        /** @var UserInterface|null $actor */
        $actor = $request->getAttribute(UserInterface::class);

        if (! $actor instanceof UserInterface) {
            return null;
        }

        $roles = [...$actor->getRoles()];

        return array_shift($roles);
    }
}
