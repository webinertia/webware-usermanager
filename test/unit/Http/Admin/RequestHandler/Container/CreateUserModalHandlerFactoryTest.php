<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\UserManager\Http\Admin\AssignableRolesProvider;
use Webware\UserManager\Http\Admin\RequestHandler\Container\CreateUserModalHandlerFactory;
use Webware\UserManager\Http\Admin\RequestHandler\CreateUserModalHandler;
use WebwareTest\UserManager\Support\MessageBus\MessageBusDoublesTrait;

#[CoversClass(CreateUserModalHandlerFactory::class)]
#[CoversMethod(CreateUserModalHandlerFactory::class, '__invoke')]
final class CreateUserModalHandlerFactoryTest extends TestCase
{
    use MessageBusDoublesTrait;

    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $this->createStub(TemplateRendererInterface::class)],
                [
                    AssignableRolesProvider::class,
                    new AssignableRolesProvider($this->messageBusStub()),
                ],
            ]);

        self::assertInstanceOf(
            CreateUserModalHandler::class,
            new CreateUserModalHandlerFactory()($container),
        );
    }
}
