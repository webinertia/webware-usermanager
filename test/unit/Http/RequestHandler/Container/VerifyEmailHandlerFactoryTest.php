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
use Webware\UserManager\Http\RequestHandler\Container\VerifyEmailHandlerFactory;
use Webware\UserManager\Http\RequestHandler\VerifyEmailHandler;
use WebwareTest\UserManager\Support\ViewHelperManagerTrait;

#[CoversClass(VerifyEmailHandlerFactory::class)]
#[CoversMethod(VerifyEmailHandlerFactory::class, '__invoke')]
final class VerifyEmailHandlerFactoryTest extends TestCase
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

        static::assertInstanceOf(VerifyEmailHandler::class, (new VerifyEmailHandlerFactory())($container));
    }
}
