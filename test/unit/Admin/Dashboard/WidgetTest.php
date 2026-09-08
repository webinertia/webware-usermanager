<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Admin\Dashboard;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Admin\Dashboard\Widget;

#[CoversClass(Widget::class)]
#[CoversMethod(Widget::class, 'getResourceId')]
final class WidgetTest extends TestCase
{
    #[Test]
    public function exposesWidgetMetadataAndResource(): void
    {
        $widget = new Widget(
            resourceId   : 'user.manager',
            totalUsers   : 12,
            activeUsers  : 8,
            inactiveUsers: 4,
        );

        self::assertSame('User Management', $widget->title);
        self::assertSame('read', $widget->privilege);
        self::assertSame('user::admin-widget', $widget->template);
        self::assertSame(5, $widget->order);
        self::assertSame('user.manager', $widget->getResourceId());
        self::assertSame(12, $widget->totalUsers);
        self::assertSame(8, $widget->activeUsers);
        self::assertSame(4, $widget->inactiveUsers);
    }
}
