<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\UserManager\RequestHandler\Container\LoginHandlerFactory;
use Webware\UserManager\RequestHandler\LoginHandler;

#[CoversClass(LoginHandlerFactory::class)]
#[CoversMethod(LoginHandlerFactory::class, '__invoke')]
final class LoginHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $this->createStub(TemplateRendererInterface::class)],
            ]);

        self::assertInstanceOf(LoginHandler::class, (new LoginHandlerFactory())($container));
    }
}
