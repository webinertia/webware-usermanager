<?php

declare(strict_types=1);

namespace Webware\UserManager\Middleware;

use Mezzio\Session\RetrieveSession;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Core\UserInterface;
use Webware\UserManager\Repository\UserRepositoryInterface;

use function is_array;

/**
 * Resolves the current identity and attaches a UserInterface to every request.
 *
 * Reads the session written by LoginMiddleware. If session data is present and
 * valid, calls the user factory to reconstruct the authenticated User. Otherwise
 * creates a GuestUser for the request.
 *
 * Always calls the next handler — access decisions are AuthorizationMiddleware's job.
 * Pipe this once in the global pipeline, after SessionMiddleware.
 */
final class IdentityMiddleware implements MiddlewareInterface
{
    /** @var callable(array): UserInterface */
    private $userFactory;

    /**
     * @param callable(array): UserInterface $userFactory
     */
    public function __construct(
        private UserRepositoryInterface $repository,
        callable $userFactory,
    ) {
        $this->userFactory = $userFactory;
    }

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session  = RetrieveSession::fromRequestOrNull($request);
        $userInfo = $session?->get(UserInterface::class);

        if (null !== $userInfo) {
            $check = $this->repository->checkStatus($userInfo['id']);
            if ($check) {
                $user = ($this->userFactory)($userInfo);
            } else {
                $session?->clear();
                $user = ($this->userFactory)(['roleId' => UserInterface::GUEST_ROLE]);
            }
        } else {
            $user = ($this->userFactory)(['roleId' => UserInterface::GUEST_ROLE]);
        }

        return $handler->handle($request->withAttribute(UserInterface::class, $user));
    }
}
