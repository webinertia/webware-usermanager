<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Middleware\Container;

use Laminas\View\HelperPluginManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Core\UserInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\Middleware\Container\ProcessVerifyEmailMiddlewareFactory;
use Webware\UserManager\Http\Middleware\ProcessVerifyEmailMiddleware;
use WebwareTest\UserManager\Support\ViewHelperManagerTrait;

#[CoversClass(ProcessVerifyEmailMiddlewareFactory::class)]
#[CoversMethod(ProcessVerifyEmailMiddlewareFactory::class, '__invoke')]
final class ProcessVerifyEmailMiddlewareFactoryTest extends TestCase
{
    use ViewHelperManagerTrait;

    #[Test]
    public function invokeBuildsMiddleware(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')
            ->willReturnMap([
                ['config', [UserInterface::class => ['verification_token_ttl' => 3600]]],
                [MessageBusInterface::class, $this->createStub(MessageBusInterface::class)],
                [HelperPluginManager::class, $this->userUrlHelperManager()],
            ]);

        static::assertInstanceOf(
            ProcessVerifyEmailMiddleware::class,
            new ProcessVerifyEmailMiddlewareFactory()($container),
        );
    }
}
