<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Middleware\Container;

use Laminas\InputFilter\InputFilterPluginManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Core\UserInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\Middleware\Container\ProcessSetPasswordMiddlewareFactory;
use Webware\UserManager\Http\Middleware\ProcessSetPasswordMiddleware;
use Webware\UserManager\InputFilter\SetPasswordDataFilter;
use WebwareTest\UserManager\Support\InputFilterHelper;

#[CoversClass(ProcessSetPasswordMiddlewareFactory::class)]
#[CoversMethod(ProcessSetPasswordMiddlewareFactory::class, '__invoke')]
final class ProcessSetPasswordMiddlewareFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsMiddleware(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')
            ->willReturnMap([
                ['config', [UserInterface::class => ['verification_token_ttl' => 3600]]],
                [MessageBusInterface::class, $this->createStub(MessageBusInterface::class)],
                [
                    InputFilterPluginManager::class,
                    InputFilterHelper::inputFilterPluginManager(),
                ],
            ]);

        static::assertInstanceOf(
            ProcessSetPasswordMiddleware::class,
            (new ProcessSetPasswordMiddlewareFactory())($container),
        );
    }

    #[Test]
    public function thePluginManagerResolvesTheFilter(): void
    {
        static::assertInstanceOf(
            SetPasswordDataFilter::class,
            InputFilterHelper::inputFilterPluginManager()->get(SetPasswordDataFilter::class),
        );
    }
}
