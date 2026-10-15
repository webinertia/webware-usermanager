<?php

declare(strict_types=1);

namespace Webware\UserManager\QueryHandler;

use Monolog\Level;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\Log\Event\LogEvent;
use Webware\Log\LogChannel;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\MessageBus\QueryHandlerInterface;
use Webware\UserManager\Auth\AuthenticationResult;
use Webware\UserManager\Auth\AuthenticationStatus;
use Webware\UserManager\Query\AuthenticateUserQuery;
use Webware\UserManager\Repository\UserRepositoryInterface;

final readonly class AuthenticateUserHandler implements QueryHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
        private EventDispatcherInterface $dispatcher,
    ) {}

    /**
     * Resolve the authentication outcome and record one security-channel audit
     * entry per attempt. Credential verification stays in the repository; the
     * account-state policy and the audit trail live here.
     *
     * Status semantics: `Failure` marks any attempt that must not establish a
     * session, which includes a denied inactive account even though its password
     * verified. `AuthenticationResult::status` carries the reason.
     */
    public function handle(AuthenticateUserQuery $query): QueryResult
    {
        $user = $this->users->authenticate($query->credential, $query->password);

        if (null === $user) {
            $this->dispatcher->dispatch(new LogEvent(LogChannel::Security, Level::Info)
                ->setMessage(
                    'Failed login attempt.',
                )
                ->setContext(['credential' => $query->credential]));

            return new QueryResult(
                $query,
                MessageStatus::Failure,
                new AuthenticationResult(AuthenticationStatus::InvalidCredentials),
            );
        }

        if (! $user->active) {
            $this->dispatcher->dispatch(new LogEvent(LogChannel::Security, Level::Info)
                ->setMessage(
                    "Failed login attempt for inactive user: {$user->email}",
                )
                ->setContext(['credential' => $query->credential]));

            return new QueryResult(
                $query,
                MessageStatus::Failure,
                new AuthenticationResult(AuthenticationStatus::NotActive),
            );
        }

        $this->dispatcher->dispatch(new LogEvent(LogChannel::Security, Level::Info)
            ->setMessage(
                "{$user->firstName} {$user->lastName} authenticated successfully.",
            )
            ->setContext(['identity' => $user->email]));

        return new QueryResult(
            $query,
            MessageStatus::Success,
            new AuthenticationResult(AuthenticationStatus::Success, $user),
        );
    }
}
