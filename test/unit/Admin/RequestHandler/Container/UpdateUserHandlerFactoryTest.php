<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\UserManager\Admin\RequestHandler\Container\UpdateUserHandlerFactory;
use Webware\UserManager\Admin\RequestHandler\UpdateUserHandler;

#[CoversClass(UpdateUserHandlerFactory::class)]
#[CoversMethod(UpdateUserHandlerFactory::class, '__invoke')]
final class UpdateUserHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsHandler(): void
    {
        $template = $this->createStub(TemplateRendererInterface::class);

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $template],
            ]);

        self::assertInstanceOf(UpdateUserHandler::class, (new UpdateUserHandlerFactory())($container));
    }
}
