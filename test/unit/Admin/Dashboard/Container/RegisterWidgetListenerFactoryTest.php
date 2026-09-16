<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Admin\Dashboard\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Admin\Container\Configuration as AdminConfiguration;
use Webware\Admin\Event\RegisterWidgetEvent;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\UserManager\Admin\Dashboard\Container\RegisterWidgetListenerFactory;
use Webware\UserManager\Admin\Dashboard\RegisterWidgetListener;
use Webware\UserManager\Admin\Dashboard\Widget;
use Webware\UserManager\Container\Configuration;
use Webware\UserManager\Query\FetchUsersQuery;

use function iterator_to_array;

#[CoversClass(RegisterWidgetListenerFactory::class)]
#[CoversMethod(RegisterWidgetListenerFactory::class, '__invoke')]
final class RegisterWidgetListenerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsListener(): void
    {
        self::assertInstanceOf(
            RegisterWidgetListener::class,
            (new RegisterWidgetListenerFactory())($this->configuredContainer($this->createStub(MessageBusInterface::class))),
        );
    }

    /**
     * The resource id is the admin prefix concatenated with this component's own
     * prefix, with the trailing separator trimmed. The widget the listener
     * contributes exposes it, so the composed value is asserted through the
     * listener rather than at the factory boundary.
     */
    #[Test]
    public function invokeComposesTheResourceIdFromBothPrefixes(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')
            ->willReturn(new QueryResult(new FetchUsersQuery(), MessageStatus::Success, []));

        $event = new RegisterWidgetEvent();

        (new RegisterWidgetListenerFactory())($this->configuredContainer($bus))($event);

        $widgets = iterator_to_array($event->getWidgetContainer());

        static::assertCount(1, $widgets);
        static::assertInstanceOf(Widget::class, $widgets[0]);
        static::assertSame('admin.user.manager', $widgets[0]->resourceId);
    }

    private function configuredContainer(MessageBusInterface $bus): ContainerInterface
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnMap([['config', true]]);
        $container->method('get')
            ->willReturnMap([
                [
                    'config',
                    [
                        AdminConfiguration::CONFIG_KEY => ['admin_route_name_prefix' => 'admin.'],
                        Configuration::CONFIG_KEY      => ['admin_route_name_prefix' => 'user.manager.'],
                    ],
                ],
                [MessageBusInterface::class, $bus],
            ]);

        return $container;
    }
}
