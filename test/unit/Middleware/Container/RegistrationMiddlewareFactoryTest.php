<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Middleware\Container;

use Laminas\InputFilter\InputFilterPluginManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Middleware\Container\RegistrationMiddlewareFactory;
use Webware\UserManager\Middleware\RegistrationMiddleware;
use WebwareTest\UserManager\Support\InputFilterHelper;

#[CoversClass(RegistrationMiddlewareFactory::class)]
#[CoversMethod(RegistrationMiddlewareFactory::class, '__invoke')]
final class RegistrationMiddlewareFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsMiddleware(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [MessageBusInterface::class, $this->createStub(MessageBusInterface::class)],
                [TemplateRendererInterface::class, $this->createStub(TemplateRendererInterface::class)],
                [InputFilterPluginManager::class, InputFilterHelper::inputFilterPluginManager()],
            ]);

        self::assertInstanceOf(RegistrationMiddleware::class, (new RegistrationMiddlewareFactory())($container));
    }
}
