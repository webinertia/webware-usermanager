<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Admin\Dashboard;

use PhpDb\ResultSet\RowPrototypeResultSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Admin\Event\RegisterWidgetEvent;
use Webware\UserManager\Admin\Dashboard\RegisterWidgetListener;
use Webware\UserManager\Admin\Dashboard\Widget;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Repository\UserRepositoryInterface;

use function iterator_to_array;

#[CoversClass(RegisterWidgetListener::class)]
#[CoversMethod(RegisterWidgetListener::class, '__construct')]
#[CoversMethod(RegisterWidgetListener::class, '__invoke')]
final class RegisterWidgetListenerTest extends TestCase
{
    #[Test]
    public function registersWidgetWithUserCounts(): void
    {
        $resultSet = new RowPrototypeResultSet(new User());
        $resultSet->initialize([
            ['id' => 1, 'active' => 1],
            ['id' => 2, 'active' => 0],
            ['id' => 3, 'active' => 1],
        ]);

        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findAll')->willReturn($resultSet);

        $listener = new RegisterWidgetListener(
            resourceId: 'user.manager',
            users     : $users,
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
