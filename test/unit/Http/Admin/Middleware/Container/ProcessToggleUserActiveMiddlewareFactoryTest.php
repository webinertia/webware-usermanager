<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Admin\Middleware\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\Admin\Middleware\Container\ProcessToggleUserActiveMiddlewareFactory;
use Webware\UserManager\Http\Admin\Middleware\ProcessToggleUserActiveMiddleware;

#[CoversClass(ProcessToggleUserActiveMiddlewareFactory::class)]
#[CoversMethod(ProcessToggleUserActiveMiddlewareFactory::class, '__invoke')]
final class ProcessToggleUserActiveMiddlewareFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsMiddleware(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [MessageBusInterface::class, $this->createStub(MessageBusInterface::class)],
            ]);

        self::assertInstanceOf(
            ProcessToggleUserActiveMiddleware::class,
            (new ProcessToggleUserActiveMiddlewareFactory())($container),
        );
    }
}
