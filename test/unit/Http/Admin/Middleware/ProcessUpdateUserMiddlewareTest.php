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
use Webware\Core\UserInterface;
use Webware\Message\SystemMessengerInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\UserManager\Command\UpdateUserCommand;
use Webware\UserManager\Http\Admin\AssignableRolesProvider;
use Webware\UserManager\Http\Admin\Middleware\ProcessUpdateUserMiddleware;
use Webware\UserManager\Query\FetchAssignableRolesQuery;
use WebwareTest\UserManager\Support\InputFilterHelper;

use function assert;
use function is_string;

#[CoversClass(ProcessUpdateUserMiddleware::class)]
#[CoversMethod(ProcessUpdateUserMiddleware::class, '__construct')]
#[CoversMethod(ProcessUpdateUserMiddleware::class, 'processPatch')]
final class ProcessUpdateUserMiddlewareTest extends TestCase
{
    #[Test]
    public function dispatchesCommandAndStoresResult(): void
    {
        $capturedCommand = null;

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->exactly(2))
            ->method('handle')
            ->willReturnCallback(static function (MessageInterface $message) use (
                &$capturedCommand,
            ): QueryResult|CommandResult {
                if ($message instanceof FetchAssignableRolesQuery) {
                    return new QueryResult($message, MessageStatus::Success, ['Member']);
                }

                assert($message instanceof UpdateUserCommand, description: 'Expected an UpdateUserCommand');
                $capturedCommand = $message;

                return new CommandResult($message, MessageStatus::Success, null);
            });

        $capturedRequest = null;
        $handler         = $this->capturingHandler($capturedRequest);

        $request = $this->patchRequest($bus, $handler, [
            'firstName' => 'Jane',
            'lastName'  => 'Doe',
            'email'     => 'jane@example.com',
            'roleId'    => 'Member',
            'active'    => '1',
        ]);

        $response = $this->middleware($bus)->processPatch($request, $handler);

        self::assertInstanceOf(EmptyResponse::class, $response);
        self::assertInstanceOf(CommandResult::class, $capturedRequest?->getAttribute(CommandResult::class));
        self::assertInstanceOf(UpdateUserCommand::class, $capturedCommand);
        self::assertSame(42, $capturedCommand->id);
        self::assertSame('Jane', $capturedCommand->firstName);
        self::assertSame('Member', $capturedCommand->roleId);
        self::assertTrue($capturedCommand->active);
    }

    #[Test]
    public function ignoresAClientSuppliedAssignableRoleList(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->willReturnCallback(static function (MessageInterface $message): QueryResult {
                assert($message instanceof FetchAssignableRolesQuery, description: 'Expected the roles query');

                return new QueryResult($message, MessageStatus::Success, ['Member']);
            });

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn(new EmptyResponse());

        $request = $this->patchRequest($bus, $handler, [
            'roleId'          => 'Administrator',
            'assignableRoles' => ['Administrator'],
        ]);

        $this->middleware($bus)->processPatch($request, $handler);
    }

    #[Test]
    public function rejectsARoleOutsideTheAssignableSetWithoutDispatching(): void
    {
        $capturedCommand = null;

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->willReturnCallback(static function (MessageInterface $message): QueryResult {
                assert($message instanceof FetchAssignableRolesQuery, description: 'Expected the roles query');

                return new QueryResult($message, MessageStatus::Success, ['Member']);
            });

        $messenger = $this->createMock(SystemMessengerInterface::class);
        $messenger->expects($this->once())
            ->method('warning')
            ->with($this->callback(is_string(...)));

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn(new EmptyResponse());

        $request = $this->patchRequest($bus, $handler, ['roleId' => 'Administrator'])
            ->withAttribute(SystemMessengerInterface::class, $messenger);

        $this->middleware($bus)->processPatch($request, $handler);

        self::assertNull($capturedCommand);
    }

    #[Test]
    public function storesFailureResultWithoutNotifying(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturnCallback(
            static function (MessageInterface $message): QueryResult|CommandResult {
                if ($message instanceof FetchAssignableRolesQuery) {
                    return new QueryResult($message, MessageStatus::Success, ['Member']);
                }

                assert($message instanceof UpdateUserCommand, description: 'Expected an UpdateUserCommand');

                return new CommandResult($message, MessageStatus::Failure, null);
            },
        );

        $messenger = $this->createMock(SystemMessengerInterface::class);
        $messenger->expects($this->never())->method('success');
        $messenger->expects($this->never())->method('danger');
        $messenger->expects($this->never())->method('warning');

        $capturedRequest = null;
        $handler         = $this->capturingHandler($capturedRequest);

        $request = $this->patchRequest($bus, $handler, [
            'firstName' => 'Jane',
            'lastName'  => 'Doe',
            'email'     => 'jane@example.com',
            'roleId'    => 'Member',
            'active'    => '1',
        ])->withAttribute(SystemMessengerInterface::class, $messenger);

        $response = $this->middleware($bus)->processPatch($request, $handler);

        self::assertInstanceOf(EmptyResponse::class, $response);
        $result = $capturedRequest?->getAttribute(CommandResult::class);
        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(MessageStatus::Failure, $result?->getStatus());
    }

    #[Test]
    public function warnsAndPassesThroughWhenValidationFails(): void
    {
        $messenger = $this->createMock(SystemMessengerInterface::class);
        $messenger->expects($this->once())
            ->method('warning')
            ->with($this->callback(is_string(...)));

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn(new EmptyResponse());

        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturnCallback(
            static fn(MessageInterface $message): QueryResult => new QueryResult(
                $message,
                MessageStatus::Success,
                ['Member'],
            ),
        );

        $request = $this->patchRequest($bus, $handler, ['email' => 'not-an-email'])
            ->withAttribute(SystemMessengerInterface::class, $messenger);

        $response = $this->middleware($bus)->processPatch($request, $handler);

        self::assertInstanceOf(EmptyResponse::class, $response);
    }

    private function actor(): UserInterface
    {
        $actor = $this->createStub(UserInterface::class);
        $actor->method('getRoles')->willReturn(['Administrator']);

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

    private function middleware(MessageBusInterface $bus): ProcessUpdateUserMiddleware
    {
        return new ProcessUpdateUserMiddleware(
            messageBus     : $bus,
            filter         : InputFilterHelper::updateUserDataFilter(),
            assignableRoles: new AssignableRolesProvider($bus),
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function patchRequest(
        MessageBusInterface $bus,
        RequestHandlerInterface $handler,
        array $overrides,
    ): ServerRequest {
        return new ServerRequest()->withMethod('PATCH')
            ->withAttribute(UserInterface::class, $this->actor())
            ->withAttribute('id', '42')
            ->withParsedBody([
                'firstName' => 'Jane',
                'lastName'  => 'Doe',
                'email'     => 'jane@example.com',
                'roleId'    => 'Member',
                ...$overrides,
            ]);
    }
}
