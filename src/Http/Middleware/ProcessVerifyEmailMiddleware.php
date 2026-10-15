<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Middleware;

use DateTimeImmutable;
use InvalidArgumentException;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\Exception\ExceptionInterface as HelperException;
use Monolog\Level;
use Override;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Log\Event\LogEvent;
use Webware\Log\LogChannel;
use Webware\Message\Exception\InvalidHopsValueException;
use Webware\Message\SystemMessengerInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\ActivateUserCommand;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Http\RequestHandler\VerifyEmailHandler;
use Webware\UserManager\Query\FetchUserByVerificationTokenQuery;
use Webware\UserManager\View\Helper\UserUrl;

/**
 * Resolves the verification token and activates the account before delegating
 * to the render-only VerifyEmailHandler.
 */
final readonly class ProcessVerifyEmailMiddleware implements MiddlewareInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private UserUrl $userUrl,
        private int $tokenTtl,
        private EventDispatcherInterface $dispatcher,
    ) {}

    /**
     * @throws HelperException
     * @throws InvalidArgumentException
     * @throws InvalidHopsValueException
     */
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var string|null $token */
        $token = $request->getAttribute('token');

        if (null === $token || '' === $token) {
            $this->audit('Email verification attempt without a token.');

            return $handler->handle($request->withAttribute(
                VerifyEmailHandler::class,
                ['error' => 'Invalid verification link.'],
            ));
        }

        $result = $this->messageBus->handle(new FetchUserByVerificationTokenQuery(token: $token));

        if ($result->getStatus() === MessageStatus::Failure) {
            $this->audit('Email verification attempt with an invalid or already used token.');

            return $handler->handle($request->withAttribute(
                VerifyEmailHandler::class,
                ['error' => 'Invalid or already used verification link.'],
            ));
        }

        /** @var User $user */
        $user = $result->getResult();

        $tokenCreatedAt = $user->tokenCreatedAt;

        if ($tokenCreatedAt instanceof DateTimeImmutable) {
            $age = new DateTimeImmutable()->getTimestamp() - $tokenCreatedAt->getTimestamp();

            if ($age > $this->tokenTtl) {
                $this->audit('Email verification attempt with an expired token.');

                return $handler->handle($request->withAttribute(
                    VerifyEmailHandler::class,
                    ['error' => 'Your verification link has expired.', 'expired' => true],
                ));
            }
        }

        if ($user->passwordSetRequired) {
            // Created without a password: send the user to set one rather than
            // activating an account nobody can sign in to. No state changes here,
            // and a mail client may prefetch the link, so this branch is not audited.
            return new RedirectResponse(
                ($this->userUrl)('set.password.read', ['token' => $token]),
            );
        }

        $commandResult = $this->messageBus->handle(new ActivateUserCommand(id: (int) $user->id));

        $this->audit(
            "{$user->firstName} {$user->lastName} verified their email and activated their account.",
            ['email' => $user->email, 'userId' => (int) $user->id],
        );

        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);
        $messenger?->success('Email verified! You may now sign in.', hops: 1, now: false);

        return $handler->handle($request->withAttribute(CommandResult::class, $commandResult));
    }

    /**
     * Record one security-channel audit entry per attempt.
     *
     * `user_identifier` is filled by webware-log's middleware from the request
     * principal, which is the guest here: the visitor holds a token rather than a
     * session, so the account under change belongs in the context.
     *
     * @param array<string, mixed> $context
     */
    private function audit(string $message, array $context = []): void
    {
        $this->dispatcher->dispatch(
            new LogEvent(LogChannel::Security, Level::Info)
                ->setMessage($message)
                ->setContext($context),
        );
    }
}
