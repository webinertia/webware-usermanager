<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Admin\RequestHandler;

use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Htmx\Response\Header;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\UserManager\Command\CreateUserCommand;
use Webware\UserManager\Http\Admin\CreateUserState;
use Webware\UserManager\Http\Admin\RequestHandler\CreateUserHandler;
use Webware\UserManager\Http\RequestHandler\UserListHandler;
use Webware\UserManager\Query\FetchUsersQuery;

use function bin2hex;
use function random_bytes;

#[CoversClass(CreateUserHandler::class)]
#[CoversMethod(CreateUserHandler::class, '__construct')]
#[CoversMethod(CreateUserHandler::class, 'handle')]
final class CreateUserHandlerTest extends TestCase
{
    private const string LIST_URL = '/admin/user';

    #[Test]
    public function delegatesToTheUserListAndClosesTheModalOnSuccess(): void
    {
        $request = new ServerRequest()->withAttribute(CreateUserState::class, new CreateUserState(
            assignableRoles: ['Member'],
            result         : new CommandResult($this->command(), MessageStatus::Success, 7),
        ));

        $response = $this->handler($this->createStub(TemplateRendererInterface::class))->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('<list>', (string) $response->getBody());
        self::assertSame(self::LIST_URL, $response->getHeaderLine(Header::PushUrl->value));
        self::assertSame('{"closeModal":null}', $response->getHeaderLine(Header::Trigger->value));
        self::assertFalse($response->hasHeader(Header::Retarget->value));
    }

    #[Test]
    public function rendersTheModalWithErrorsAndOldInputAt422(): void
    {
        $template = $this->expectingTemplateRender([
            'assignableRoles' => ['Member'],
            'errors'          => ['roleId' => ['The selected role is not one you may assign.']],
            'old'             => ['email' => 'jane@example.com', 'roleId' => 'Developer'],
            'layout'          => false,
            'body'            => false,
        ]);

        $response = $this->handler($template)->handle(
            new ServerRequest()->withAttribute(
                CreateUserState::class,
                new CreateUserState(
                    assignableRoles: ['Member'],
                    errors         : ['roleId' => ['The selected role is not one you may assign.']],
                    old            : ['email' => 'jane@example.com', 'roleId' => 'Developer'],
                ),
            ),
        );

        self::assertSame(422, $response->getStatusCode());
        self::assertSame('#sharedModalDialog', $response->getHeaderLine(Header::Retarget->value));
        self::assertSame('innerHTML', $response->getHeaderLine(Header::Reswap->value));
    }

    #[Test]
    public function rendersTheModalWithOldInputAt500WhenTheCommandFailed(): void
    {
        $template = $this->expectingTemplateRender([
            'assignableRoles' => ['Member'],
            'errors'          => [],
            'old'             => ['email' => 'jane@example.com'],
            'layout'          => false,
            'body'            => false,
        ]);

        $response = $this->handler($template)->handle(
            new ServerRequest()->withAttribute(
                CreateUserState::class,
                new CreateUserState(
                    assignableRoles: ['Member'],
                    old            : ['email' => 'jane@example.com'],
                    result         : new CommandResult(
                        $this->command(),
                        MessageStatus::Failure,
                        'Failed to save user.',
                    ),
                ),
            ),
        );

        self::assertSame(500, $response->getStatusCode());
        self::assertSame('#sharedModalDialog', $response->getHeaderLine(Header::Retarget->value));
        self::assertSame('innerHTML', $response->getHeaderLine(Header::Reswap->value));
    }

    #[Test]
    public function rendersTheModalWithoutAState(): void
    {
        $template = $this->expectingTemplateRender([
            'assignableRoles' => [],
            'errors'          => [],
            'old'             => [],
            'layout'          => false,
            'body'            => false,
        ]);

        $response = $this->handler($template)->handle(new ServerRequest());

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('<form>', (string) $response->getBody());
        self::assertFalse($response->hasHeader(Header::Retarget->value));
    }

    #[Test]
    public function rendersTheModalWithTheAssignableRoles(): void
    {
        $template = $this->expectingTemplateRender([
            'assignableRoles' => ['Member', 'Administrator'],
            'errors'          => [],
            'old'             => [],
            'layout'          => false,
            'body'            => false,
        ]);

        $response = $this->handler($template)->handle(
            new ServerRequest()->withAttribute(
                CreateUserState::class,
                new CreateUserState(assignableRoles: ['Member', 'Administrator']),
            ),
        );

        self::assertSame(200, $response->getStatusCode());
    }

    private function command(): CreateUserCommand
    {
        return new CreateUserCommand(
            firstName        : 'Jane',
            lastName         : 'Doe',
            passwordHash     : bin2hex(random_bytes(16)),
            email            : 'jane@example.com',
            roleId           : 'Member',
            verificationToken: bin2hex(random_bytes(16)),
        );
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function expectingTemplateRender(array $variables): TemplateRendererInterface
    {
        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::create-user-modal', $variables)
            ->willReturn('<form>');

        return $template;
    }

    private function handler(TemplateRendererInterface $template): CreateUserHandler
    {
        $listTemplate = $this->createStub(TemplateRendererInterface::class);
        $listTemplate->method('render')->willReturn('<list>');

        $messageBus = $this->createStub(MessageBusInterface::class);
        $messageBus->method('handle')
            ->willReturn(
                new QueryResult(new FetchUsersQuery(), MessageStatus::Success, []),
            );

        return new CreateUserHandler(
            template   : $template,
            listHandler: new UserListHandler(
                template  : $listTemplate,
                messageBus: $messageBus,
            ),
            listUrl    : self::LIST_URL,
        );
    }
}
