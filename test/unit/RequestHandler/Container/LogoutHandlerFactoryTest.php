<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\RequestHandler\Container;

use Laminas\View\HelperPluginManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\UserManager\RequestHandler\Container\LogoutHandlerFactory;
use Webware\UserManager\RequestHandler\LogoutHandler;
use WebwareTest\UserManager\Support\ViewHelperManagerTrait;

#[CoversClass(LogoutHandlerFactory::class)]
#[CoversMethod(LogoutHandlerFactory::class, '__invoke')]
final class LogoutHandlerFactoryTest extends TestCase
{
    use ViewHelperManagerTrait;

    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [HelperPluginManager::class, $this->userUrlHelperManager()],
            ]);

        self::assertInstanceOf(LogoutHandler::class, (new LogoutHandlerFactory())($container));
    }
}
