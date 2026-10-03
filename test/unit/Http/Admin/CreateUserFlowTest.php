<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Admin;

use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Webware\Core\UserInterface;
use Webware\Htmx\Response\Header;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\UserManager\Http\Admin\AssignableRolesProvider;
use Webware\UserManager\Http\Admin\Middleware\ProcessCreateUserMiddleware;
use Webware\UserManager\Http\Admin\RequestHandler\CreateUserHandler;
use Webware\UserManager\Http\RequestHandler\UserListHandler;
use Webware\UserManager\Query\FetchAssignableRolesQuery;
use WebwareTest\UserManager\Support\InputFilterHelper;

/**
 * Runs the real create-user middleware into the real create-user handler.
 *
 * This is the pair that has to agree on where the outcome of a POST is stored;
 * unit tests of either half alone would not catch a mismatch.
 */
#[CoversClass(ProcessCreateUserMiddleware::class)]
#[CoversMethod(ProcessCreateUserMiddleware::class, '__construct')]
#[CoversMethod(ProcessCreateUserMiddleware::class, 'processPost')]
#[CoversClass(CreateUserHandler::class)]
#[CoversMethod(CreateUserHandler::class, '__construct')]
#[CoversMethod(CreateUserHandler::class, 'handle')]
final class CreateUserFlowTest extends TestCase
{
    private const string LIST_URL = '/admin/user';

    #[Test]
    public function aFailedCommandReturnsTheModalAt500WithTheAssignableRoles(): void
    {
        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::create-user-modal', [
                'assignableRoles' => ['Member'],
                'errors'          => [],
                'old'             => [
                    'firstName' => 'Jane',
                    'lastName'  => 'Doe',
                    'email'     => 'jane@example.com',
                    'roleId'    => 'Member',
                ],
                'layout'          => false,
                'body'            => false,
            ])
            ->willReturn('<form>');

        $response = $this->dispatch(
            $this->validRequest(),
            $this->bus(['Member'], MessageStatus::Failure),
            $template,
        );

        self::assertSame(500, $response->getStatusCode());
        self::assertSame('<form>', (string) $response->getBody());
        self::assertSame('#sharedModalDialog', $response->getHeaderLine(Header::Retarget->value));
        self::assertSame('innerHTML', $response->getHeaderLine(Header::Reswap->value));
    }

    #[Test]
    public function aValidationFailureReturnsTheModalAt422WithErrorsAndOldInput(): void
    {
        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::create-user-modal', [
                'assignableRoles' => ['Member'],
                'errors'          => ['roleId' => ['The selected role is not one you may assign.']],
                'old'             => [
                    'firstName' => 'Jane',
                    'lastName'  => 'Doe',
                    'email'     => 'jane@example.com',
                    'roleId'    => 'Developer',
                ],
                'layout'          => false,
                'body'            => false,
            ])
            ->willReturn('<form>');

        $response = $this->dispatch(
            $this->validRequest(['roleId' => 'Developer']),
            $this->bus(['Member'], MessageStatus::Success),
            $template,
        );

        self::assertSame(422, $response->getStatusCode());
        self::assertSame('#sharedModalDialog', $response->getHeaderLine(Header::Retarget->value));
        self::assertSame('innerHTML', $response->getHeaderLine(Header::Reswap->value));
    }

    #[Test]
    public function successReturnsTheUserListAndClosesTheModal(): void
    {
        $response = $this->dispatch(
            $this->validRequest(),
            $this->bus(['Member'], MessageStatus::Success),
            $this->createStub(TemplateRendererInterface::class),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('<list>', (string) $response->getBody());
        self::assertSame(self::LIST_URL, $response->getHeaderLine(Header::PushUrl->value));
        self::assertSame('{"closeModal":null}', $response->getHeaderLine(Header::Trigger->value));
    }

    /**
     * @param list<string> $assignableRoles
     */
    private function bus(array $assignableRoles, MessageStatus $status): MessageBusInterface
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturnCallback(
            static fn(MessageInterface $message): QueryResult|CommandResult => $message
                instanceof FetchAssignableRolesQuery
                    ? new QueryResult($message, MessageStatus::Success, $assignableRoles)
                    : new CommandResult($message, $status, 7),
        );

        return $bus;
    }

    private function dispatch(
        ServerRequest $request,
        MessageBusInterface $bus,
        TemplateRendererInterface $template,
    ): ResponseInterface {
        $listTemplate = $this->createStub(TemplateRendererInterface::class);
        $listTemplate->method('render')->willReturn('<list>');

        $handler = new CreateUserHandler(
            template   : $template,
            listHandler: new UserListHandler(
                template: $listTemplate,
            ),
            listUrl    : self::LIST_URL,
        );

        $middleware = new ProcessCreateUserMiddleware(
            messageBus     : $bus,
            filter         : InputFilterHelper::createUserDataFilter(),
            logger         : $this->createStub(LoggerInterface::class),
            assignableRoles: new AssignableRolesProvider($bus),
        );

        return $middleware->processPost($request, $handler);
    }

    /**
     * @param array<string, string> $overrides
     */
    private function validRequest(array $overrides = []): ServerRequest
    {
        $actor = $this->createStub(UserInterface::class);
        $actor->method('getRoles')->willReturn(['Administrator']);
        $actor->method('getIdentity')->willReturn('admin@example.com');

        return new ServerRequest()->withMethod('POST')
            ->withAttribute(UserInterface::class, $actor)
            ->withParsedBody([
                'firstName' => 'Jane',
                'lastName'  => 'Doe',
                'email'     => 'jane@example.com',
                'roleId'    => 'Member',
                ...$overrides,
            ]);
    }
}
