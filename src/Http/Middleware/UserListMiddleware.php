<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Middleware;

use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Query\FetchUsersQuery;

/**
 * Assembles the user list view model and attaches it to the request as an
 * attribute before passing control to the handler that renders it.
 *
 * Data assembly is kept out of the request handler, which is render-only: it
 * reads the attribute and calls render(). Same split as
 * Mezzio\Router\Middleware\RouteMiddleware, and as webware-acl's
 * OverviewMiddleware/AclOverviewHandler pair.
 *
 * Attribute key: UserListMiddleware::class
 *
 * View model shape:
 *   users  list<\Webware\UserManager\Entity\User>
 *
 * Pipe this ahead of the list handler, and on the write routes after the
 * Process* middleware, so the list reflects the command that has just run.
 */
final readonly class UserListMiddleware implements MiddlewareInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {}

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var list<\Webware\UserManager\Entity\User> $users */
        $users = $this->messageBus->handle(new FetchUsersQuery())->getResult();

        return $handler->handle($request->withAttribute(self::class, ['users' => $users]));
    }
}
