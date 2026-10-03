<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Admin\Middleware;

use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Webware\Core\UserInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\UserManager\Command\CreateUserCommand;
use Webware\UserManager\Http\Admin\CreateUserState;
use Webware\UserManager\Http\Admin\Middleware\ProcessCreateUserMiddleware;
use Webware\UserManager\Query\FetchAssignableRolesQuery;
use WebwareTest\UserManager\Support\InputFilterHelper;

use function assert;

#[CoversClass(ProcessCreateUserMiddleware::class)]
#[CoversMethod(ProcessCreateUserMiddleware::class, '__construct')]
#[CoversMethod(ProcessCreateUserMiddleware::class, 'processPost')]
final class ProcessCreateUserMiddlewareTest extends TestCase
{
    #[Test]
    public function attachesOnlyTheCommandResultOnSuccess(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturnCallback(
            static fn(MessageInterface $message): QueryResult|CommandResult => (
                $message instanceof FetchAssignableRolesQuery
                    ? new QueryResult($message, MessageStatus::Success, ['Member'])
                    : new CommandResult($message, MessageStatus::Success, 7)
            ),
        );

        $capturedRequest = null;

        $this->middleware($bus, $this->createStub(LoggerInterface::class))->processPost(
            $this->postRequest(['roleId' => 'Member']),
            $this->capturingHandler($capturedRequest),
        );

        self::assertNull($capturedRequest?->getAttribute(CreateUserState::class));
        self::assertInstanceOf(CommandResult::class, $capturedRequest?->getAttribute(CommandResult::class));
    }

    #[Test]
    public function attachesTheOldInputWithoutAWarningWhenAFieldIsMissing(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->willReturnCallback(
                static fn(FetchAssignableRolesQuery $query): QueryResult => new QueryResult(
                    $query,
                    MessageStatus::Success,
                    ['Member'],
                ),
            );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');

        $capturedRequest = null;
        $request         = new ServerRequest()->withMethod('POST')
            ->withAttribute(UserInterface::class, $this->actor())
            ->withParsedBody(['firstName' => 'Jane', 'lastName' => 'Doe', 'roleId' => 'Member']);

        $this->middleware($bus, $logger)->processPost($request, $this->capturingHandler($capturedRequest));

        $state = $capturedRequest?->getAttribute(CreateUserState::class);
        self::assertInstanceOf(CreateUserState::class, $state);
        self::assertArrayHasKey('email', $state->errors);
        self::assertArrayNotHasKey('roleId', $state->errors);
    }

    #[Test]
    public function dispatchesTheCommandAndStoresTheResult(): void
    {
        $captured = null;

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->exactly(2))
            ->method('handle')
            ->willReturnCallback(static function (MessageInterface $message) use (
                &$captured,
            ): QueryResult|CommandResult {
                if ($message instanceof FetchAssignableRolesQuery) {
                    return new QueryResult($message, MessageStatus::Success, ['Member', 'Administrator']);
                }

                assert($message instanceof CreateUserCommand, description: 'Expected a CreateUserCommand');
                $captured = $message;

                return new CommandResult($message, MessageStatus::Success, 7);
            });

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Created user', [
                'actor'  => 'admin@example.com',
                'userId' => 7,
                'roleId' => 'Member',
            ]);
        $logger->expects($this->never())->method('warning');
        $capturedRequest = null;
        $handler         = $this->capturingHandler($capturedRequest);

        $response = $this->middleware($bus, $logger)->processPost(
            $this->postRequest(['roleId' => 'Member']),
            $handler,
        );

        self::assertInstanceOf(EmptyResponse::class, $response);
        self::assertInstanceOf(CreateUserCommand::class, $captured);
        self::assertSame('Jane', $captured->firstName);
        self::assertSame('jane@example.com', $captured->email);
        self::assertSame('Member', $captured->roleId);
        self::assertFalse($captured->active);
        self::assertNotSame('', $captured->verificationToken);

        $state = $capturedRequest?->getAttribute(CreateUserState::class);
        self::assertNull($state);
        self::assertInstanceOf(CommandResult::class, $capturedRequest?->getAttribute(CommandResult::class));
    }

    #[Test]
    public function failsClosedWhenTheActorAttributeIsMissing(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('handle');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('Rejected a role outside the assignable set', ['roleId' => 'Member']);

        $capturedRequest = null;
        $request         = new ServerRequest()->withMethod('POST')
            ->withParsedBody([
                'firstName' => 'Jane',
                'lastName'  => 'Doe',
                'email'     => 'jane@example.com',
                'roleId'    => 'Member',
            ]);

        $this->middleware($bus, $logger)->processPost($request, $this->capturingHandler($capturedRequest));

        $state = $capturedRequest?->getAttribute(CreateUserState::class);
        self::assertInstanceOf(CreateUserState::class, $state);
        self::assertSame([], $state->assignableRoles);
        self::assertArrayHasKey('roleId', $state->errors);
    }

    #[Test]
    public function failsClosedWhenTheActorHasNoRole(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturnCallback(
            static fn(MessageInterface $message): QueryResult|CommandResult => (
                $message instanceof FetchAssignableRolesQuery
                    ? new QueryResult($message, MessageStatus::Success, [])
                    : new CommandResult($message, MessageStatus::Success, 7)
            ),
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('Rejected a role outside the assignable set', ['roleId' => 'Member']);

        $capturedRequest = null;
        $request         = $this->postRequest(['roleId' => 'Member']);

        $this->middleware($bus, $logger)->processPost($request, $this->capturingHandler($capturedRequest));

        $state = $capturedRequest?->getAttribute(CreateUserState::class);
        self::assertInstanceOf(CreateUserState::class, $state);
        self::assertSame([], $state->assignableRoles);
        self::assertArrayHasKey('roleId', $state->errors);
    }

    #[Test]
    public function ignoresAClientSuppliedAssignableRoleList(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturnCallback(
            static fn(MessageInterface $message): QueryResult|CommandResult => (
                $message instanceof FetchAssignableRolesQuery
                    ? new QueryResult($message, MessageStatus::Success, ['Member'])
                    : new CommandResult($message, MessageStatus::Success, 7)
            ),
        );

        $capturedRequest = null;

        $response = $this->middleware($bus, $this->createStub(LoggerInterface::class))->processPost(
            $this->postRequest(['roleId' => 'Developer', 'assignableRoles' => ['Developer']]),
            $this->capturingHandler($capturedRequest),
        );

        self::assertInstanceOf(EmptyResponse::class, $response);
        $state = $capturedRequest?->getAttribute(CreateUserState::class);
        self::assertInstanceOf(CreateUserState::class, $state);
        self::assertArrayHasKey('roleId', $state->errors);
        self::assertNull($capturedRequest?->getAttribute(CommandResult::class));
    }

    #[Test]
    public function storesTheFailureResultWithoutLoggingSuccess(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturnCallback(
            static fn(MessageInterface $message): QueryResult|CommandResult => (
                $message instanceof FetchAssignableRolesQuery
                    ? new QueryResult($message, MessageStatus::Success, ['Member'])
                    : new CommandResult($message, MessageStatus::Failure, 'Failed to save user.')
            ),
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('info');

        $capturedRequest = null;

        $this->middleware($bus, $logger)->processPost(
            $this->postRequest(['roleId' => 'Member']),
            $this->capturingHandler($capturedRequest),
        );

        $result = $capturedRequest?->getAttribute(CommandResult::class);
        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(MessageStatus::Failure, $result->getStatus());
    }

    #[Test]
    public function warnsAndAttachesStateWhenTheRoleIsNotAssignable(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->willReturnCallback(
                static fn(FetchAssignableRolesQuery $query): QueryResult => new QueryResult(
                    $query,
                    MessageStatus::Success,
                    ['Member'],
                ),
            );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('Rejected a role outside the assignable set', ['roleId' => 'Developer']);
        $logger->expects($this->never())->method('info');

        $capturedRequest = null;

        $this->middleware($bus, $logger)->processPost(
            $this->postRequest(['roleId' => 'Developer']),
            $this->capturingHandler($capturedRequest),
        );

        $state = $capturedRequest?->getAttribute(CreateUserState::class);
        self::assertInstanceOf(CreateUserState::class, $state);
        self::assertSame(
            ['roleId' => ['The selected role is not one you may assign.']],
            $state->errors,
        );
        self::assertSame(
            ['firstName' => 'Jane', 'lastName' => 'Doe', 'email' => 'jane@example.com', 'roleId' => 'Developer'],
            $state->old,
        );
    }

    private function actor(): UserInterface
    {
        $actor = $this->createStub(UserInterface::class);
        $actor->method('getRoles')->willReturn(['Administrator']);
        $actor->method('getIdentity')->willReturn('admin@example.com');

        return $actor;
    }

    private function capturingHandler(?ServerRequestInterface &$capturedRequest): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(static function (ServerRequestInterface $request) use (
                &$capturedRequest,
            ): ResponseInterface {
                $capturedRequest = $request;

                return new EmptyResponse();
            });

        return $handler;
    }

    private function middleware(MessageBusInterface $bus, LoggerInterface $logger): ProcessCreateUserMiddleware
    {
        return new ProcessCreateUserMiddleware(
            messageBus: $bus,
            filter    : InputFilterHelper::createUserDataFilter(),
            logger    : $logger,
        );
    }

    /**
     * @param array<string, string> $overrides
     */
    private function postRequest(array $overrides): ServerRequest
    {
        return new ServerRequest()->withMethod('POST')
            ->withAttribute(UserInterface::class, $this->actor())
            ->withParsedBody([
                'firstName' => 'Jane',
                'lastName'  => 'Doe',
                'email'     => 'jane@example.com',
                ...$overrides,
            ]);
    }
}
