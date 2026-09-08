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
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\MailerInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\MessageBus\ResultInterface;
use Webware\UserManager\Command\RegenerateVerificationTokenCommand;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Http\Middleware\ProcessResendVerificationMiddleware;
use Webware\UserManager\Http\RequestHandler\ResendVerificationHandler;
use Webware\UserManager\Query\FetchUserByEmail;
use Webware\UserManager\View\Helper\UserUrl;

use function bin2hex;
use function random_bytes;

#[CoversClass(ProcessResendVerificationMiddleware::class)]
#[CoversMethod(ProcessResendVerificationMiddleware::class, '__construct')]
#[CoversMethod(ProcessResendVerificationMiddleware::class, 'process')]
final class ProcessResendVerificationMiddlewareTest extends TestCase
{
    private const array MAIL_CONFIG = [
        'from_email'                 => 'noreply@example.com',
        'from_name'                  => 'Webware',
        'base_url'                   => 'https://example.com/',
        'verification_email_subject' => 'Verify your email',
    ];

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
                static fn(MessageInterface $message): ResultInterface => $message instanceof FetchUserByEmail
                    ? new QueryResult($message, MessageStatus::Success, $user)
                    : $commandResult,
            );

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())->method('getAdapter');
        $mailer->expects($this->never())->method('send');

        $request = $this->process(
            bus    : $bus,
            mailer : $mailer,
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
                new FetchUserByEmail(email: 'jane@example.com'),
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

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->exactly(2))
            ->method('handle')
            ->willReturnCallback(
                static fn(MessageInterface $message): ResultInterface => $message instanceof FetchUserByEmail
                    ? new QueryResult($message, MessageStatus::Success, $user)
                    : $commandResult,
            );

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())->method('from')->with('noreply@example.com', 'Webware')->willReturnSelf();
        $adapter->expects($this->once())->method('to')->with('jane@example.com', 'Jane Doe')->willReturnSelf();
        $adapter->expects($this->once())->method('subject')->with('Verify your email')->willReturnSelf();
        $adapter->expects($this->once())->method('isHtml')->with(true)->willReturnSelf();
        $adapter->expects($this->once())
            ->method('body')
            ->with(
                '<p>Hello Jane,</p>'
                    . '<p>You requested a new verification link. Please verify your email address by clicking below.</p>'
                    . '<p><a href="https://example.com/user/verify-email">Verify my email</a></p>'
                    . '<p>This link expires in 24 hours.</p>',
            )
            ->willReturnSelf();
        $adapter->expects($this->once())
            ->method('altBody')
            ->with(
                "Hello Jane,\n\n"
                    . "You requested a new verification link. Please visit:\n"
                    . "https://example.com/user/verify-email\n\n"
                    . "This link expires in 24 hours.\n",
            )
            ->willReturnSelf();

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('getAdapter')->willReturn($adapter);
        $mailer->expects($this->once())->method('send')->willReturn(true);

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
            mailer   : $mailer,
            urlHelper: $urlHelper,
            request  : new ServerRequest()->withMethod('POST')
                ->withParsedBody(['email' => 'jane@example.com']),
        );

        static::assertSame(['sent' => true], $request->getAttribute(ResendVerificationHandler::class));
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
                new FetchUserByEmail(email: 'ghost@example.com'),
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
        ?MailerInterface $mailer = null,
        ?UrlHelper $urlHelper = null,
    ): ServerRequestInterface {
        $mailer ??= $this->createStub(MailerInterface::class);

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
            messageBus: $bus,
            mailer    : $mailer,
            userUrl   : new UserUrl($urlHelper, 'user.manager.'),
            mailConfig: self::MAIL_CONFIG,
        )->process($request, $handler);

        return $capturedRequest;
    }
}
