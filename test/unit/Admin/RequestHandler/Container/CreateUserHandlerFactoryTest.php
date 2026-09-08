<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\UserManager\Admin\RequestHandler\Container\CreateUserHandlerFactory;
use Webware\UserManager\Admin\RequestHandler\CreateUserHandler;

#[CoversClass(CreateUserHandlerFactory::class)]
#[CoversMethod(CreateUserHandlerFactory::class, '__invoke')]
final class CreateUserHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $this->createStub(TemplateRendererInterface::class)],
            ]);

        self::assertInstanceOf(CreateUserHandler::class, (new CreateUserHandlerFactory())($container));
    }
}
