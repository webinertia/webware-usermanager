<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Admin\Middleware;

use Override;
use Psl\Type\Exception\AssertException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Core\UserInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Http\Admin\AssignableRolesProvider;
use Webware\UserManager\Query\FetchUserByIdQuery;

use function filter_var;

use const FILTER_VALIDATE_INT;

/**
 * Assembles the update-user modal view model and attaches it to the request as
 * an attribute before passing control to the handler that renders it.
 *
 * Data assembly is kept out of the request handler, which is render-only: it
 * reads the attribute and calls render(). Same split as
 * Mezzio\Router\Middleware\RouteMiddleware, and as webware-acl's
 * OverviewMiddleware/AclOverviewHandler pair.
 *
 * Attribute key: UpdateUserModalMiddleware::class
 *
 * View model shape:
 *   user             ?UserInterface   null when the id is unusable or no such user exists
 *   assignableRoles  list<string>
 *
 * A missing or unusable id carries null rather than short-circuiting: returning
 * the 404 is a render decision, so it stays in the handler.
 */
final readonly class UpdateUserModalMiddleware implements MiddlewareInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private AssignableRolesProvider $assignableRoles,
    ) {}

    /**
     * @return array{user: ?UserInterface, assignableRoles: list<string>}
     */
    private static function emptyViewModel(): array
    {
        return ['user' => null, 'assignableRoles' => []];
    }

    /**
     * @throws AssertException
     */
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $id = filter_var($request->getAttribute('id'), FILTER_VALIDATE_INT);

        if (false === $id) {
            return $handler->handle($request->withAttribute(self::class, self::emptyViewModel()));
        }

        $result = $this->messageBus->handle(new FetchUserByIdQuery(id: $id));

        if (MessageStatus::Failure === $result->getStatus()) {
            return $handler->handle($request->withAttribute(self::class, self::emptyViewModel()));
        }

        /** @var UserInterface $user */
        $user = $result->getResult();

        return $handler->handle($request->withAttribute(self::class, [
            'user'            => $user,
            'assignableRoles' => $this->assignableRoles->forRequest($request),
        ]));
    }
}
