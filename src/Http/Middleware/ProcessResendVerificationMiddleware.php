<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Middleware;

use DateTimeImmutable;
use Fig\Http\Message\RequestMethodInterface;
use InvalidArgumentException;
use Mezzio\Helper\Exception\ExceptionInterface as HelperException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Uuid;
use Webware\Core\UserInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\RegenerateVerificationTokenCommand;
use Webware\UserManager\Command\ResendVerificationEmailCommand;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Http\RequestHandler\ResendVerificationHandler;
use Webware\UserManager\Query\FetchUserByEmailQuery;
use Webware\UserManager\View\Helper\UserUrl;

use function is_array;
use function is_string;
use function rtrim;

/**
 * Handles the resend-verification POST: looks up the user, regenerates the
 * token, and sends the email before delegating to the render-only
 * ResendVerificationHandler.
 */
final readonly class ProcessResendVerificationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private UserUrl $userUrl,
        private string $baseUrl,
        private string $verificationSubject,
    ) {}

    /**
     * @throws HelperException
     * @throws InvalidArgumentException
     */
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() !== RequestMethodInterface::METHOD_POST) {
            return $handler->handle($request);
        }

        $body  = $request->getParsedBody();
        $email = is_array($body) && is_string($body['email'] ?? null) ? $body['email'] : '';

        if ('' === $email) {
            return $handler->handle($request->withAttribute(
                ResendVerificationHandler::class,
                ['error' => 'Please enter a valid email address.'],
            ));
        }

        $result = $this->messageBus->handle(new FetchUserByEmailQuery(email: $email));

        if ($result->getStatus() === MessageStatus::Failure) {
            // Silently skip unknown emails — do not reveal whether the address
            // is registered (prevents user enumeration).
            return $handler->handle($request->withAttribute(
                ResendVerificationHandler::class,
                ['sent' => true],
            ));
        }

        /** @var User $user */
        $user = $result->getResult();

        if (true === $user->active) {
            return $handler->handle($request->withAttribute(
                ResendVerificationHandler::class,
                ['redirect' => true],
            ));
        }

        $token = Uuid::uuid7()->toString();
        $now   = new DateTimeImmutable()->format(UserInterface::DATETIME_FORMAT);

        $commandResult = $this->messageBus->handle(new RegenerateVerificationTokenCommand(
            id            : (int) $user->id,
            token         : $token,
            tokenCreatedAt: $now,
        ));

        /** @var int $updated */
        $updated = $commandResult->getResult();

        if ($updated > 0) {
            $verificationUrl =
                rtrim(
                    string    : $this->baseUrl,
                    characters: '/',
                )
                . ($this->userUrl)('verify.email.read', ['token' => $token]);

            $this->messageBus->handle(new ResendVerificationEmailCommand(
                to             : $email,
                toName         : ($user->firstName ?? '') . ' ' . ($user->lastName ?? ''),
                firstName      : $user->firstName ?? '',
                verificationUrl: $verificationUrl,
                subject        : $this->verificationSubject,
            ));
        }

        return $handler->handle($request->withAttribute(
            ResendVerificationHandler::class,
            ['sent' => true],
        ));
    }
}
