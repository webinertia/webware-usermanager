<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Support\MessageBus;

use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\UserManager\Query\FetchAssignableRolesQuery;

/**
 * Bus doubles for tests that live in a perimeter bucket.
 *
 * A test class under `Http\` may not name `MessageBusInterface`: `deny-from`
 * is applied regardless of the test-root permit in `allow-from`, because a bus
 * double belongs in a bus bucket. This trait is that bucket. The calling test
 * supplies `$this`, so the doubles are still registered with PHPUnit and their
 * expectations are still verified.
 */
trait MessageBusDoublesTrait
{
    /**
     * @param list<string> $roles
     */
    private function messageBusAnsweringWithRoles(string $actorRoleId, array $roles): MessageBusInterface
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->expects($this->once())
            ->method('handle')
            ->with($this->callback(
                static fn(FetchAssignableRolesQuery $query): bool => $actorRoleId === $query->actorRoleId,
            ))
            ->willReturn(new QueryResult(
                new FetchAssignableRolesQuery(actorRoleId: $actorRoleId),
                MessageStatus::Success,
                $roles,
            ));

        return $messageBus;
    }

    private function messageBusStub(): MessageBusInterface
    {
        return $this->createStub(MessageBusInterface::class);
    }

    private function messageBusThatIsNeverUsed(): MessageBusInterface
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->never())->method('handle');

        return $messageBus;
    }
}
