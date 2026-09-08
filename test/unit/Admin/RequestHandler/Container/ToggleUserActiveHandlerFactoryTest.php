<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\UserManager\Admin\RequestHandler\Container\ToggleUserActiveHandlerFactory;
use Webware\UserManager\Admin\RequestHandler\ToggleUserActiveHandler;

#[CoversClass(ToggleUserActiveHandlerFactory::class)]
#[CoversMethod(ToggleUserActiveHandlerFactory::class, '__invoke')]
final class ToggleUserActiveHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $this->createStub(TemplateRendererInterface::class)],
            ]);

        self::assertInstanceOf(ToggleUserActiveHandler::class, (new ToggleUserActiveHandlerFactory())($container));
    }
}
