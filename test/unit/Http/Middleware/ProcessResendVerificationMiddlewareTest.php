<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Middleware;

use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\MessageBus\ResultInterface;
use Webware\UserManager\Command\RegenerateVerificationTokenCommand;
use Webware\UserManager\Command\ResendVerificationEmailCommand;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Http\Middleware\ProcessResendVerificationMiddleware;
use Webware\UserManager\Http\RequestHandler\ResendVerificationHandler;
use Webware\UserManager\Query\FetchUserByEmailQuery;
use Webware\UserManager\View\Helper\UserUrl;

use function bin2hex;
use function random_bytes;

#[CoversClass(ProcessResendVerificationMiddleware::class)]
#[CoversMethod(ProcessResendVerificationMiddleware::class, '__construct')]
#[CoversMethod(ProcessResendVerificationMiddleware::class, 'process')]
final class ProcessResendVerificationMiddlewareTest extends TestCase
{
    #[Test]
    public function dispatchesResendVerificationEmailCommand(): void
    {
        $user = new User(
            id       : 7,
            firstName: 'Jane',
            lastName : 'Doe',
            active   : false,
        );

        $commandResult = new CommandResult(
            new RegenerateVerificationTokenCommand(
                id            : 7,
                token         : bin2hex(random_bytes(16)),
                tokenCreatedAt: '2026-09-08 12:00:00',
            ),
            MessageStatus::Success,
            1,
        );

        $dispatched = [];

        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')
            ->willReturnCallback(
                static function (MessageInterface $message) use (&$dispatched, $user, $commandResult): ResultInterface {
                    $dispatched[] = $message;

                    return $message instanceof FetchUserByEmailQuery
                        ? new QueryResult($message, MessageStatus::Success, $user)
                        : $commandResult;
                },
            );

        $this->process(
            bus: $bus,
            request: new ServerRequest()->withMethod('POST')
                ->withParsedBody(['email' => 'jane@example.com']),
        );

        $email = $dispatched[2] ?? null;

        if (! $email instanceof ResendVerificationEmailCommand) {
            static::fail('Expected a ResendVerificationEmailCommand to be dispatched.');
        }

        static::assertSame('jane@example.com', $email->to);
        static::assertSame('Jane Doe', $email->toName);
        static::assertSame('Jane', $email->firstName);
        static::assertSame('https://example.com/user/verify-email', $email->verificationUrl);
        static::assertSame('Verify your email', $email->subject);
    }

    #[Test]
    public function doesNotSendEmailWhenUpdateAffectsZeroRows(): void
    {
        $user = new User(
            id       : 7,
            firstName: 'Jane',
            lastName : 'Doe',
            active   : false,
        );

        $commandResult = new CommandResult(
            new RegenerateVerificationTokenCommand(
                id            : 7,
                token         : bin2hex(random_bytes(16)),
                tokenCreatedAt: '2026-09-08 12:00:00',
            ),
            MessageStatus::Success,
            0,
        );

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->exactly(2))
            ->method('handle')
            ->willReturnCallback(
                static fn(MessageInterface $message): ResultInterface => $message instanceof FetchUserByEmailQuery
                    ? new QueryResult($message, MessageStatus::Success, $user)
                    : $commandResult,
            );

        $request = $this->process(
            bus: $bus,
            request: new ServerRequest()->withMethod('POST')
                ->withParsedBody(['email' => 'jane@example.com']),
        );

        static::assertSame(['sent' => true], $request->getAttribute(ResendVerificationHandler::class));
    }

    #[Test]
    public function passesThroughNonPostRequests(): void
    {
        $request = $this->process(
            bus    : $this->createStub(MessageBusInterface::class),
            request: new ServerRequest()->withMethod('GET'),
        );

        static::assertNull($request->getAttribute(ResendVerificationHandler::class));
    }

    #[Test]
    public function redirectsActiveUser(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')
            ->willReturn(new QueryResult(
                new FetchUserByEmailQuery(email: 'jane@example.com'),
                MessageStatus::Success,
                new User(
                    id    : 7,
                    active: true,
                ),
            ));

        $request = $this->process(
            bus: $bus,
            request: new ServerRequest()->withMethod('POST')
                ->withParsedBody(['email' => 'jane@example.com']),
        );

        static::assertSame(['redirect' => true], $request->getAttribute(ResendVerificationHandler::class));
    }

    #[Test]
    public function regeneratesTokenAndSendsEmailForInactiveUser(): void
    {
        $user = new User(
            id       : 7,
            firstName: 'Jane',
            lastName : 'Doe',
            active   : false,
        );

        $commandResult = new CommandResult(
            new RegenerateVerificationTokenCommand(
                id            : 7,
                token         : bin2hex(random_bytes(16)),
                tokenCreatedAt: '2026-09-08 12:00:00',
            ),
            MessageStatus::Success,
            1,
        );

        $dispatched = [];

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->exactly(3))
            ->method('handle')
            ->willReturnCallback(
                static function (MessageInterface $message) use (&$dispatched, $user, $commandResult): ResultInterface {
                    $dispatched[] = $message;

                    return $message instanceof FetchUserByEmailQuery
                        ? new QueryResult($message, MessageStatus::Success, $user)
                        : $commandResult;
                },
            );

        $urlHelper = $this->createMock(UrlHelper::class);
        $urlHelper->expects($this->once())
            ->method('__invoke')
            ->with(
                'user.manager.verify.email.read',
                static::callback(static fn(array $params): bool => '' !== ($params['token'] ?? '')),
                [],
                null,
                [],
            )
            ->willReturn('/user/verify-email');

        $request = $this->process(
            bus      : $bus,
            urlHelper: $urlHelper,
            request  : new ServerRequest()->withMethod('POST')
                ->withParsedBody(['email' => 'jane@example.com']),
        );

        static::assertSame(['sent' => true], $request->getAttribute(ResendVerificationHandler::class));

        $email = $dispatched[2] ?? null;

        if (! $email instanceof ResendVerificationEmailCommand) {
            static::fail('Expected a ResendVerificationEmailCommand to be dispatched.');
        }

        static::assertSame('jane@example.com', $email->to);
        static::assertSame('Jane Doe', $email->toName);
        static::assertSame('https://example.com/user/verify-email', $email->verificationUrl);
    }

    #[Test]
    public function setsErrorOnEmptyEmail(): void
    {
        $request = $this->process(
            bus    : $this->createStub(MessageBusInterface::class),
            request: new ServerRequest()->withMethod('POST')
                ->withParsedBody(['email' => '']),
        );

        static::assertSame(
            ['error' => 'Please enter a valid email address.'],
            $request->getAttribute(ResendVerificationHandler::class),
        );
    }

    #[Test]
    public function silentlySkipsUnknownEmail(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')
            ->willReturn(new QueryResult(
                new FetchUserByEmailQuery(email: 'ghost@example.com'),
                MessageStatus::Failure,
                null,
            ));

        $request = $this->process(
            bus: $bus,
            request: new ServerRequest()->withMethod('POST')
                ->withParsedBody(['email' => 'ghost@example.com']),
        );

        static::assertSame(['sent' => true], $request->getAttribute(ResendVerificationHandler::class));
    }

    private function process(
        MessageBusInterface $bus,
        ServerRequestInterface $request,
        ?UrlHelper $urlHelper = null,
    ): ServerRequestInterface {
        if (null === $urlHelper) {
            $urlHelper = $this->createStub(UrlHelper::class);
            $urlHelper->method('__invoke')->willReturn('/user/verify-email');
        }

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

        new ProcessResendVerificationMiddleware(
            messageBus         : $bus,
            userUrl            : new UserUrl($urlHelper, 'user.manager.'),
            baseUrl            : 'https://example.com/',
            verificationSubject: 'Verify your email',
        )->process($request, $handler);

        return $capturedRequest;
    }
}
