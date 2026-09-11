<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Middleware;

use Mezzio\Session\RetrieveSession;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Core\UserInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Query\CheckUserActiveQuery;

/**
 * Resolves the current identity and attaches a UserInterface to every request.
 *
 * Reads the session written by LoginMiddleware. When a payload is present and the
 * account is still active (CheckUserActiveQuery), the user factory reconstructs
 * the authenticated User from the stored row. Otherwise the session is cleared and
 * a User carrying UserInterface::GUEST_ROLE is attached.
 *
 * Always calls the next handler — access decisions are AuthorizationMiddleware's job.
 * Pipe this once in the global pipeline, after SessionMiddleware.
 */
final class IdentityMiddleware implements MiddlewareInterface
{
    /** @var callable(array<string, mixed>): UserInterface */
    private $userFactory;

    /**
     * @param callable(array<string, mixed>): UserInterface $userFactory
     */
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        callable $userFactory,
    ) {
        $this->userFactory = $userFactory;
    }

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = RetrieveSession::fromRequestOrNull($request);

        /** @var array<string, mixed>|null $userInfo */
        $userInfo = $session?->get(UserInterface::class);

        if (null === $userInfo) {
            $user = ($this->userFactory)(['roleId' => UserInterface::GUEST_ROLE]);
        } else {
            /** @var bool $check */
            $check = $this->messageBus->handle(new CheckUserActiveQuery(id: (int) ($userInfo['id'] ?? 0)))->getResult();

            if ($check) {
                $user = ($this->userFactory)($userInfo);
            } else {
                $session?->clear();
                $user = ($this->userFactory)(['roleId' => UserInterface::GUEST_ROLE]);
            }
        }

        return $handler->handle($request->withAttribute(UserInterface::class, $user));
    }
}
