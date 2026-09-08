<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\RetrieveSession;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Webware\Core\UserInterface;
use Webware\Message\SystemMessengerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Auth\AuthenticationResult;
use Webware\UserManager\Auth\AuthenticationStatus;
use Webware\UserManager\Query\AuthenticateUser;

use function is_string;

final class LoginMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
        private readonly string $redirectUrl,
    ) {}

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (RequestMethodInterface::METHOD_POST !== $request->getMethod()) {
            return $handler->handle($request);
        }

        /** @var array<string, mixed> $params */
        $params = $request->getParsedBody() ?? [];

        $email    = $params['email'] ?? null;
        $password = $params['password'] ?? null;

        if (! is_string($email) || ! is_string($password)) {
            return $handler->handle($request);
        }

        /** @var AuthenticationResult $result */
        $result = $this->messageBus->handle(
            new AuthenticateUser(
                credential: $email,
                password  : $password,
            ),
        )->getResult();

        if (AuthenticationStatus::Success !== $result->status) {
            $this->logger->info('Failed login attempt', ['email' => $email]);
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
