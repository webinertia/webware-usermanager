<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\Admin\RequestHandler\Container\UpdateUserModalHandlerFactory;
use Webware\UserManager\Http\Admin\RequestHandler\UpdateUserModalHandler;

#[CoversClass(UpdateUserModalHandlerFactory::class)]
#[CoversMethod(UpdateUserModalHandlerFactory::class, '__invoke')]
final class UpdateUserModalHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $this->createStub(TemplateRendererInterface::class)],
                [MessageBusInterface::class, $this->createStub(MessageBusInterface::class)],
            ]);

        self::assertInstanceOf(UpdateUserModalHandler::class, (new UpdateUserModalHandlerFactory())($container));
    }
}
