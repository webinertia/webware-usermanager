<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\RequestHandler\Container;

use Laminas\View\HelperPluginManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\UserManager\Http\RequestHandler\Container\RegistrationHandlerFactory;
use Webware\UserManager\Http\RequestHandler\RegistrationHandler;
use WebwareTest\UserManager\Support\ViewHelperManagerTrait;

#[CoversClass(RegistrationHandlerFactory::class)]
#[CoversMethod(RegistrationHandlerFactory::class, '__invoke')]
final class RegistrationHandlerFactoryTest extends TestCase
{
    use ViewHelperManagerTrait;

    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $this->createStub(TemplateRendererInterface::class)],
                [HelperPluginManager::class, $this->userUrlHelperManager()],
            ]);

        self::assertInstanceOf(RegistrationHandler::class, (new RegistrationHandlerFactory())($container));
    }
}
