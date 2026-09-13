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
use Webware\Mailer\MailerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\RegenerateVerificationTokenCommand;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Http\RequestHandler\ResendVerificationHandler;
use Webware\UserManager\Query\FetchUserByEmailQuery;
use Webware\UserManager\View\Helper\UserUrl;

use function htmlspecialchars;
use function is_array;
use function is_string;
use function rtrim;

use const ENT_QUOTES;

/**
 * Handles the resend-verification POST: looks up the user, regenerates the
 * token, and sends the email before delegating to the render-only
 * ResendVerificationHandler.
 */
final readonly class ProcessResendVerificationMiddleware implements MiddlewareInterface
{
    /**
     * @param array{from_email: string, from_name: string, base_url: string, verification_email_subject: string} $mailConfig
     */
    public function __construct(
        private MessageBusInterface $messageBus,
        private MailerInterface $mailer,
        private UserUrl $userUrl,
        private array $mailConfig,
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
            $adapter = $this->mailer->getAdapter();

            if (null !== $adapter) {
                $verificationUrl =
                    rtrim(
                        string    : $this->mailConfig['base_url'],
                        characters: '/',
                    )
                    . ($this->userUrl)('verify.email.read', ['token' => $token]);

                $adapter->from($this->mailConfig['from_email'], $this->mailConfig['from_name'])
                    ->to($email, ($user->firstName ?? '') . ' ' . ($user->lastName ?? ''))
                    ->subject($this->mailConfig['verification_email_subject'])
                    ->isHtml(true)
                    ->body(
                        '<p>Hello '
                            . htmlspecialchars($user->firstName ?? '', flags: ENT_QUOTES, encoding: 'UTF-8')
                            . ',</p>'
                            . '<p>You requested a new verification link. Please verify your email address by clicking below.</p>'
                            . '<p><a href="'
                            . htmlspecialchars($verificationUrl, flags: ENT_QUOTES, encoding: 'UTF-8')
                            . '">Verify my email</a></p>'
                            . '<p>This link expires in 24 hours.</p>',
                    )
                    ->altBody(
                        'Hello '
                            . ($user->firstName ?? '')
                            . ",\n\n"
                            . "You requested a new verification link. Please visit:\n"
                            . $verificationUrl
                            . "\n\n"
                            . "This link expires in 24 hours.\n",
                    );

                $this->mailer->send();
            }
        }

        return $handler->handle($request->withAttribute(
            ResendVerificationHandler::class,
            ['sent' => true],
        ));
    }
}
