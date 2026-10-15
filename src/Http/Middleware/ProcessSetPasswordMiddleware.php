<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Middleware;

use DateTimeImmutable;
use Fig\Http\Message\RequestMethodInterface;
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
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\ActivateUserCommand;
use Webware\UserManager\Command\SetPasswordCommand;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Http\RequestHandler\SetPasswordHandler;
use Webware\UserManager\InputFilter\SetPasswordDataFilter;
use Webware\UserManager\Query\FetchUserByVerificationTokenQuery;

use function array_filter;
use function array_map;
use function array_values;
use function is_array;
use function is_string;

/**
 * Resolves the token, then either renders the set-password form or, on POST,
 * sets the password and activates the account.
 *
 * The token is the same one the verification flow uses, so an account is only
 * ever activated with a password its owner chose.
 */
final readonly class ProcessSetPasswordMiddleware implements MiddlewareInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private SetPasswordDataFilter $filter,
        private int $tokenTtl,
        private EventDispatcherInterface $dispatcher,
    ) {}

    /**
     * @throws InvalidHopsValueException
     */
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var string|null $token */
        $token = $request->getAttribute('token');

        if (! is_string($token) || '' === $token) {
            $this->audit('Set-password attempt without a token.');

            return $this->fail($request, $handler, 'Invalid verification link.');
        }

        $result = $this->messageBus->handle(new FetchUserByVerificationTokenQuery(token: $token));

        if ($result->getStatus() === MessageStatus::Failure) {
            $this->audit('Set-password attempt with an invalid or already used token.');

            return $this->fail($request, $handler, 'Invalid or already used verification link.');
        }

        /** @var User $user */
        $user = $result->getResult();

        if ($this->isExpired($user)) {
            $this->audit('Set-password attempt with an expired token.');

            return $this->fail(
                $request,
                $handler,
                'Your verification link has expired.',
                expired: true,
            );
        }

        if (! $user->passwordSetRequired) {
            $this->audit(
                'Set-password attempt for an account that already has a password.',
                $this->identifiersOf($user),
            );

            return $this->fail($request, $handler, 'This account already has a password. Please sign in.');
        }

        return (
            RequestMethodInterface::METHOD_POST === $request->getMethod()
                ? $this->setPassword($request, $handler, $user)
                : $handler->handle($request->withAttribute(
                    SetPasswordHandler::class,
                    ['token' => $token],
                ))
        );
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

    /**
     * @param array<string, array<array-key, string>> $messages
     *
     * @return array<string, list<string>>
     */
    private function errors(array $messages): array
    {
        return array_filter(array_map(array_values(...), $messages));
    }

    private function fail(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        string $error,
        bool $expired = false,
    ): ResponseInterface {
        return $handler->handle($request->withAttribute(
            SetPasswordHandler::class,
            ['error' => $error, 'expired' => $expired],
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function identifiersOf(User $user): array
    {
        return ['email' => $user->email, 'userId' => (int) $user->id];
    }

    private function isExpired(User $user): bool
    {
        $tokenCreatedAt = $user->tokenCreatedAt;

        if (! $tokenCreatedAt instanceof DateTimeImmutable) {
            return false;
        }

        return (new DateTimeImmutable()->getTimestamp() - $tokenCreatedAt->getTimestamp()) > $this->tokenTtl;
    }

    /**
     * @throws InvalidHopsValueException
     */
    private function setPassword(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        User $user,
    ): ResponseInterface {
        $body = $request->getParsedBody();

        /** @var array<string, mixed> $posted */
        $posted = is_array($body) ? $body : [];

        $filterResult = $this->filter->validate($posted);

        if (! $filterResult->valid()) {
            /** @var array<string, array<array-key, string>> $messages */
            $messages = $filterResult->getMessages()->toArray();

            return $handler->handle($request->withAttribute(
                SetPasswordHandler::class,
                ['errors' => $this->errors($messages), 'status' => 422],
            ));
        }

        /** @var array{passwordHash: string} $values */
        $values = $filterResult->value();

        $this->messageBus->handle(new SetPasswordCommand(
            id          : (int) $user->id,
            passwordHash: $values['passwordHash'],
        ));
        $this->messageBus->handle(new ActivateUserCommand(id: (int) $user->id));

        $this->audit(
            "{$user->firstName} {$user->lastName} set a password and activated their account.",
            $this->identifiersOf($user),
        );

        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);
        $messenger?->success('Your password is set. You may now sign in.', hops: 1, now: false);

        return $handler->handle($request->withAttribute(
            SetPasswordHandler::class,
            ['success' => true],
        ));
    }
}
