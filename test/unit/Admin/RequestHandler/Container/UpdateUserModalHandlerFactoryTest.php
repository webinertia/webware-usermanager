<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\UserManager\Admin\RequestHandler\Container\UpdateUserModalHandlerFactory;
use Webware\UserManager\Admin\RequestHandler\UpdateUserModalHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

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
                [UserRepositoryInterface::class, $this->createStub(UserRepositoryInterface::class)],
            ]);

        self::assertInstanceOf(UpdateUserModalHandler::class, (new UpdateUserModalHandlerFactory())($container));
    }
}
