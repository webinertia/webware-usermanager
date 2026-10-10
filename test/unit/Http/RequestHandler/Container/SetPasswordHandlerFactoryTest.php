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
use Webware\UserManager\Http\RequestHandler\Container\SetPasswordHandlerFactory;
use Webware\UserManager\Http\RequestHandler\SetPasswordHandler;
use WebwareTest\UserManager\Support\ViewHelperManagerTrait;

#[CoversClass(SetPasswordHandlerFactory::class)]
#[CoversMethod(SetPasswordHandlerFactory::class, '__invoke')]
final class SetPasswordHandlerFactoryTest extends TestCase
{
    use ViewHelperManagerTrait;

    #[Test]
    public function invokeBuildsTheHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [HelperPluginManager::class, $this->userUrlHelperManager()],
                [TemplateRendererInterface::class, $this->createStub(TemplateRendererInterface::class)],
            ]);

        static::assertInstanceOf(
            SetPasswordHandler::class,
            new SetPasswordHandlerFactory()($container),
        );
    }
}
