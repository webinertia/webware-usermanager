<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Admin\Middleware\Container;

use Laminas\InputFilter\InputFilterPluginManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\Admin\Middleware\Container\ProcessCreateUserMiddlewareFactory;
use Webware\UserManager\Http\Admin\Middleware\ProcessCreateUserMiddleware;
use WebwareTest\UserManager\Support\InputFilterHelper;

#[CoversClass(ProcessCreateUserMiddlewareFactory::class)]
#[CoversMethod(ProcessCreateUserMiddlewareFactory::class, '__invoke')]
final class ProcessCreateUserMiddlewareFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsMiddlewareWithTheCreateFilter(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [InputFilterPluginManager::class, InputFilterHelper::inputFilterPluginManager()],
                [MessageBusInterface::class, $this->createStub(MessageBusInterface::class)],
                [LoggerInterface::class, $this->createStub(LoggerInterface::class)],
            ]);

        self::assertInstanceOf(
            ProcessCreateUserMiddleware::class,
            (new ProcessCreateUserMiddlewareFactory())($container),
        );
    }
}
