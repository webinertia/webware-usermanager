<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Middleware;

use DateTimeImmutable;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Message\Exception\InvalidHopsValueException;
use Webware\Message\SystemMessengerInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\ActivateUserCommand;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Http\RequestHandler\VerifyEmailHandler;
use Webware\UserManager\Query\FetchUserByVerificationToken;

/**
 * Resolves the verification token and activates the account before delegating
 * to the render-only VerifyEmailHandler.
 */
final readonly class ProcessVerifyEmailMiddleware implements MiddlewareInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private int $tokenTtl,
    ) {}

    /**
     * @throws InvalidHopsValueException
     */
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var string|null $token */
        $token = $request->getAttribute('token');

        if (null === $token || '' === $token) {
            return $handler->handle($request->withAttribute(
                VerifyEmailHandler::class,
                ['error' => 'Invalid verification link.'],
            ));
        }

        $result = $this->messageBus->handle(new FetchUserByVerificationToken(token: $token));

        if ($result->getStatus() === MessageStatus::Failure) {
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
                return $handler->handle($request->withAttribute(
                    VerifyEmailHandler::class,
                    ['error' => 'Your verification link has expired.', 'expired' => true],
                ));
            }
        }

        $commandResult = $this->messageBus->handle(new ActivateUserCommand(id: (int) $user->id));

        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);
        $messenger?->success('Email verified! You may now sign in.', hops: 1, now: false);

        return $handler->handle($request->withAttribute(CommandResult::class, $commandResult));
    }
}
