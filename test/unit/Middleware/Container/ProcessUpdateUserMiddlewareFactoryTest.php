<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Middleware\Container;

use Laminas\InputFilter\InputFilterPluginManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Middleware\Container\ProcessUpdateUserMiddlewareFactory;
use Webware\UserManager\Middleware\ProcessUpdateUserMiddleware;
use WebwareTest\UserManager\Support\InputFilterHelper;

#[CoversClass(ProcessUpdateUserMiddlewareFactory::class)]
#[CoversMethod(ProcessUpdateUserMiddlewareFactory::class, '__invoke')]
final class ProcessUpdateUserMiddlewareFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsMiddleware(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [InputFilterPluginManager::class, InputFilterHelper::inputFilterPluginManager()],
                [MessageBusInterface::class, $this->createStub(MessageBusInterface::class)],
            ]);

        self::assertInstanceOf(
            ProcessUpdateUserMiddleware::class,
            (new ProcessUpdateUserMiddlewareFactory())($container),
        );
    }
}
