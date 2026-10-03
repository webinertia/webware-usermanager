<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Middleware;

use DateTimeImmutable;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Message\SystemMessengerInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\MessageBus\ResultInterface;
use Webware\UserManager\Command\ActivateUserCommand;
use Webware\UserManager\Command\SetPasswordCommand;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Http\Middleware\ProcessSetPasswordMiddleware;
use Webware\UserManager\Http\RequestHandler\SetPasswordHandler;
use Webware\UserManager\Query\FetchUserByVerificationTokenQuery;
use WebwareTest\UserManager\Support\InputFilterHelper;

use function bin2hex;
use function random_bytes;

#[CoversClass(ProcessSetPasswordMiddleware::class)]
#[CoversMethod(ProcessSetPasswordMiddleware::class, '__construct')]
#[CoversMethod(ProcessSetPasswordMiddleware::class, 'process')]
final class ProcessSetPasswordMiddlewareTest extends TestCase
{
    #[Test]
    public function rejectsAMissingToken(): void
    {
        $request = $this->process(
            bus    : $this->createStub(MessageBusInterface::class),
            request: new ServerRequest(),
        );

        static::assertSame(
            ['error' => 'Invalid verification link.', 'expired' => false],
            $request->getAttribute(SetPasswordHandler::class),
        );
    }

    #[Test]
    public function rejectsAnAccountThatAlreadyHasAPassword(): void
    {
        $token = bin2hex(random_bytes(16));

        $request = $this->process(
            bus    : $this->busReturning(new User(id: 4)),
            request: new ServerRequest()->withAttribute('token', $token),
        );

        static::assertSame(
            ['error' => 'This account already has a password. Please sign in.', 'expired' => false],
            $request->getAttribute(SetPasswordHandler::class),
        );
    }

    #[Test]
    public function rejectsAnExpiredToken(): void
    {
        $token = bin2hex(random_bytes(16));
        $user  = new User(
            id                 : 4,
            tokenCreatedAt     : new DateTimeImmutable('-2 hours'),
            passwordSetRequired: 1,
        );

        $request = $this->process(
            bus    : $this->busReturning($user),
            request: new ServerRequest()->withAttribute('token', $token),
        );

        static::assertSame(
            ['error' => 'Your verification link has expired.', 'expired' => true],
            $request->getAttribute(SetPasswordHandler::class),
        );
    }

    #[Test]
    public function rejectsAnUnknownToken(): void
    {
        $token = bin2hex(random_bytes(16));

        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturn(new QueryResult(
            new FetchUserByVerificationTokenQuery(token: $token),
            MessageStatus::Failure,
            null,
        ));

        $request = $this->process(
            bus: $bus,
            request: new ServerRequest()->withAttribute('token', $token),
        );

        static::assertSame(
            ['error' => 'Invalid or already used verification link.', 'expired' => false],
            $request->getAttribute(SetPasswordHandler::class),
        );
    }

    #[Test]
    public function rendersTheFormOnGet(): void
    {
        $token = bin2hex(random_bytes(16));

        $request = $this->process(
            bus    : $this->busReturning($this->flaggedUser()),
            request: new ServerRequest()->withAttribute('token', $token),
        );

        static::assertSame(
            ['token' => $token],
            $request->getAttribute(SetPasswordHandler::class),
        );
    }

    #[Test]
    public function reRendersWithErrorsWhenThePasswordsDoNotMatch(): void
    {
        $token = bin2hex(random_bytes(16));

        $request = $this->process(
            bus    : $this->busReturning($this->flaggedUser()),
            request: new ServerRequest([], [], null, 'POST')->withAttribute('token', $token)
                ->withParsedBody([
                    'passwordHash'        => 'correct horse battery staple',
                    'confirmPasswordHash' => 'a different password',
                ]),
        );

        /** @var array{errors: array<string, list<string>>, status: int} $params */
        $params = $request->getAttribute(SetPasswordHandler::class);

        static::assertSame(422, $params['status']);
        static::assertSame(['Passwords do not match.'], $params['errors']['confirmPasswordHash']);
    }

    #[Test]
    public function setsThePasswordThenActivatesTheAccount(): void
    {
        $token = bin2hex(random_bytes(16));
        $user  = $this->flaggedUser();

        $dispatched = [];
        $bus        = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->exactly(3))
            ->method('handle')
            ->willReturnCallback(
                static function (MessageInterface $message) use (&$dispatched, $user): ResultInterface {
                    $dispatched[] = $message;

                    if ($message instanceof FetchUserByVerificationTokenQuery) {
                        return new QueryResult($message, MessageStatus::Success, $user);
                    }

                    return new CommandResult($message, MessageStatus::Success, 1);
                },
            );

        $messenger = $this->createMock(SystemMessengerInterface::class);
        $messenger->expects($this->once())
            ->method('success')
            ->with('Your password is set. You may now sign in.', 1, false);

        $request = $this->process(
            bus: $bus,
            request: new ServerRequest([], [], null, 'POST')->withAttribute('token', $token)
                ->withAttribute(SystemMessengerInterface::class, $messenger)
                ->withParsedBody([
                    'passwordHash'        => 'correct horse battery staple',
                    'confirmPasswordHash' => 'correct horse battery staple',
                ]),
        );

        static::assertSame(['success' => true], $request->getAttribute(SetPasswordHandler::class));
        static::assertInstanceOf(SetPasswordCommand::class, $dispatched[1]);
        static::assertInstanceOf(ActivateUserCommand::class, $dispatched[2]);
    }

    #[Test]
    public function treatsAMissingTokenCreatedAtAsNotExpired(): void
    {
        $token = bin2hex(random_bytes(16));
        $user  = new User(
            id                 : 4,
            passwordSetRequired: 1,
        );

        $request = $this->process(
            bus    : $this->busReturning($user),
            request: new ServerRequest()->withAttribute('token', $token),
        );

        static::assertSame(
            ['token' => $token],
            $request->getAttribute(SetPasswordHandler::class),
        );
    }

    #[Test]
    public function treatsANonArrayBodyAsEmptyOnPost(): void
    {
        $token = bin2hex(random_bytes(16));

        $request = $this->process(
            bus    : $this->busReturning($this->flaggedUser()),
            request: new ServerRequest([], [], null, 'POST')->withAttribute('token', $token),
        );

        /** @var array{errors: array<string, list<string>>, status: int} $params */
        $params = $request->getAttribute(SetPasswordHandler::class);

        static::assertSame(422, $params['status']);
    }

    #[Test]
    public function treatsANonDateTokenTimestampAsNotExpired(): void
    {
        $token = bin2hex(random_bytes(16));
        // A row whose tokenCreatedAt did not survive hydration must not read as expired.
        $user = new User(
            id                 : 4,
            tokenCreatedAt     : ['date' => null],
            passwordSetRequired: 1,
        );

        $request = $this->process(
            bus    : $this->busReturning($user),
            request: new ServerRequest()->withAttribute('token', $token),
        );

        static::assertSame(
            ['token' => $token],
            $request->getAttribute(SetPasswordHandler::class),
        );
    }

    private function busReturning(User $user): MessageBusInterface
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturnCallback(
            static fn(MessageInterface $message): ResultInterface => new QueryResult(
                $message,
                MessageStatus::Success,
                $user,
            ),
        );

        return $bus;
    }

    private function flaggedUser(): User
    {
        return new User(
            id                 : 4,
            passwordSetRequired: 1,
        );
    }

    private function process(
        MessageBusInterface $bus,
        ServerRequestInterface $request,
        int $tokenTtl = 3600,
    ): ServerRequestInterface {
        $capturedRequest = null;
        $handler         = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(static function (ServerRequestInterface $req) use (
                &$capturedRequest,
            ): ResponseInterface {
                $capturedRequest = $req;

                return new EmptyResponse();
            });

        new ProcessSetPasswordMiddleware(
            messageBus: $bus,
            filter    : InputFilterHelper::setPasswordDataFilter(),
            tokenTtl  : $tokenTtl,
        )->process($request, $handler);

        return $capturedRequest;
    }
}
