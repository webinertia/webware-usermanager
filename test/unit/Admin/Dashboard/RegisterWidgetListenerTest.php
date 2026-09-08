<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Admin\Dashboard;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Admin\Event\RegisterWidgetEvent;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\UserManager\Admin\Dashboard\RegisterWidgetListener;
use Webware\UserManager\Admin\Dashboard\Widget;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Query\FetchUsers;

use function iterator_to_array;

#[CoversClass(RegisterWidgetListener::class)]
#[CoversMethod(RegisterWidgetListener::class, '__construct')]
#[CoversMethod(RegisterWidgetListener::class, '__invoke')]
final class RegisterWidgetListenerTest extends TestCase
{
    #[Test]
    public function registersWidgetWithUserCounts(): void
    {
        $messageBus = $this->createStub(MessageBusInterface::class);
        $messageBus->method('handle')
            ->willReturn(new QueryResult(new FetchUsers(), MessageStatus::Success, [
                new User(
                    id    : 1,
                    active: true,
                ),
                new User(
                    id    : 2,
                    active: false,
                ),
                new User(
                    id    : 3,
                    active: true,
                ),
            ]));

        $listener = new RegisterWidgetListener(
            resourceId: 'user.manager',
            messageBus: $messageBus,
        );

        $event = new RegisterWidgetEvent();
        $listener($event);

        $widgets = iterator_to_array($event->getWidgetContainer());

        self::assertCount(1, $widgets);

        $widget = $widgets[0];

        self::assertInstanceOf(Widget::class, $widget);
        self::assertSame('user.manager', $widget->getResourceId());
        self::assertSame(3, $widget->totalUsers);
        self::assertSame(2, $widget->activeUsers);
        self::assertSame(1, $widget->inactiveUsers);
    }
}
