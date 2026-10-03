<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Admin\RequestHandler;

use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Core\UserInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\UserManager\Http\Admin\RequestHandler\CreateUserModalHandler;
use Webware\UserManager\Query\FetchAssignableRolesQuery;

#[CoversClass(CreateUserModalHandler::class)]
#[CoversMethod(CreateUserModalHandler::class, '__construct')]
#[CoversMethod(CreateUserModalHandler::class, 'handle')]
final class CreateUserModalHandlerTest extends TestCase
{
    #[Test]
    public function rendersAnEmptyRoleListWhenTheActorAttributeIsMissing(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->never())->method('handle');

        $template = $this->expectingEmptyRoles();

        $handler = new CreateUserModalHandler(
            template  : $template,
            messageBus: $messageBus,
        );

        self::assertSame(200, $handler->handle(new ServerRequest())->getStatusCode());
    }

    #[Test]
    public function rendersAnEmptyRoleListWhenTheActorHasNoRole(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->never())->method('handle');

        $template = $this->expectingEmptyRoles();

        $handler = new CreateUserModalHandler(
            template  : $template,
            messageBus: $messageBus,
        );

        $response = $handler->handle($this->requestWithActor(null));

        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function rendersTheModalWithTheAssignableRoles(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())
            ->method('handle')
            ->with($this->callback(
                static fn(FetchAssignableRolesQuery $query): bool => 'Administrator' === $query->actorRoleId,
            ))
            ->willReturn(new QueryResult(
                new FetchAssignableRolesQuery(actorRoleId: 'Administrator'),
                MessageStatus::Success,
                ['Administrator', 'Member'],
            ));

        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::create-user-modal', [
                'assignableRoles' => ['Administrator', 'Member'],
                'errors'          => [],
                'old'             => [],
                'layout'          => false,
                'body'            => false,
            ])
            ->willReturn('<form>');

        $handler = new CreateUserModalHandler(
            template  : $template,
            messageBus: $messageBus,
        );

        $response = $handler->handle($this->requestWithActor('Administrator'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('<form>', (string) $response->getBody());
    }

    private function expectingEmptyRoles(): TemplateRendererInterface
    {
        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::create-user-modal', [
                'assignableRoles' => [],
                'errors'          => [],
                'old'             => [],
                'layout'          => false,
                'body'            => false,
            ])
            ->willReturn('<form>');

        return $template;
    }

    private function requestWithActor(?string $roleId): ServerRequest
    {
        $actor = $this->createStub(UserInterface::class);
        $actor->method('getRoles')->willReturn(null === $roleId ? [] : [$roleId]);

        return new ServerRequest()->withAttribute(UserInterface::class, $actor);
    }
}
