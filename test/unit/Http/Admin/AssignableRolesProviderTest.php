<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Admin;

use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\AssertException;
use Webware\Core\UserInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\UserManager\Http\Admin\AssignableRolesProvider;
use Webware\UserManager\Query\FetchAssignableRolesQuery;

#[CoversClass(AssignableRolesProvider::class)]
#[CoversMethod(AssignableRolesProvider::class, '__construct')]
#[CoversMethod(AssignableRolesProvider::class, 'forRequest')]
final class AssignableRolesProviderTest extends TestCase
{
    #[Test]
    public function asksForTheRolesTheActorsOwnRoleMayAssign(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->with($this->callback(
                static fn(FetchAssignableRolesQuery $query): bool => 'Administrator' === $query->actorRoleId,
            ))
            ->willReturn(new QueryResult(
                new FetchAssignableRolesQuery(actorRoleId: 'Administrator'),
                MessageStatus::Success,
                ['Administrator', 'Member'],
            ));

        $roles = new AssignableRolesProvider($bus)->forRequest($this->requestWithRoles(['Administrator']));

        self::assertSame(['Administrator', 'Member'], $roles);
    }

    #[Test]
    public function rejectsAPayloadThatIsNotAListOfStrings(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturn(new QueryResult(
            new FetchAssignableRolesQuery(actorRoleId: 'Administrator'),
            MessageStatus::Success,
            [1, 2],
        ));

        $provider = new AssignableRolesProvider($bus);

        $this->expectException(AssertException::class);

        $provider->forRequest($this->requestWithRoles(['Administrator']));
    }

    #[Test]
    public function returnsNoRolesWhenTheActorAttributeIsMissing(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('handle');

        self::assertSame([], new AssignableRolesProvider($bus)->forRequest(new ServerRequest()));
    }

    #[Test]
    public function returnsNoRolesWhenTheActorCarriesNoRole(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('handle');

        self::assertSame([], new AssignableRolesProvider($bus)->forRequest($this->requestWithRoles([])));
    }

    /**
     * @param list<string> $roles
     */
    private function requestWithRoles(array $roles): ServerRequest
    {
        $actor = $this->createStub(UserInterface::class);
        $actor->method('getRoles')->willReturn($roles);

        return new ServerRequest()->withAttribute(UserInterface::class, $actor);
    }
}
