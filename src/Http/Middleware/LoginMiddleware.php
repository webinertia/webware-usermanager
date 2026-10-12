<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Middleware;

use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\Exception\ExceptionInterface as SessionException;
use Mezzio\Session\RetrieveSession;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Core\UserInterface;
use Webware\Message\Exception;
use Webware\Message\SystemMessengerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Auth\AuthenticationResult;
use Webware\UserManager\Auth\AuthenticationStatus;
use Webware\UserManager\Query\AuthenticateUserQuery;

use function is_string;

final class LoginMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly string $credentialField,
        private readonly string $passwordField,
        private readonly string $redirectUrl,
    ) {}

    /**
     * Registered on the POST-only login route, so there is no request method to
     * branch on. A body without a usable credential and password falls through
     * to the login handler, which re-renders the form.
     *
     * @throws Exception\ExceptionInterface
     * @throws SessionException
     */
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var array<string, mixed> $params */
        $params = $request->getParsedBody() ?? [];

        $credential = $params[$this->credentialField] ?? null;
        $password   = $params[$this->passwordField] ?? null;

        if (! is_string($credential) || ! is_string($password)) {
            return $handler->handle($request);
        }

        /** @var AuthenticationResult $result */
        $result = $this->messageBus->handle(
            new AuthenticateUserQuery(
                credential: $credential,
                password  : $password,
            ),
        )->getResult();

        if (AuthenticationStatus::Success !== $result->status) {
            /** @var SystemMessengerInterface|null $messenger */
            $messenger = $request->getAttribute(SystemMessengerInterface::class);
            $messenger?->danger('Invalid email or password.');

            if (AuthenticationStatus::NotActive === $result->status) {
                $messenger?->info('Did you activate your account?');
            }

            return $handler->handle($request);
        }

        $session = RetrieveSession::fromRequest($request);
        $session->set(UserInterface::class, $result->user->toArray());

        $session->regenerate();

        return new RedirectResponse($this->redirectUrl);
    }
}
