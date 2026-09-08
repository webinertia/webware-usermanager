<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Middleware\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Container\Configuration;
use Webware\UserManager\Http\Middleware\Container\LoginMiddlewareFactory;
use Webware\UserManager\Http\Middleware\LoginMiddleware;

#[CoversClass(LoginMiddlewareFactory::class)]
#[CoversMethod(LoginMiddlewareFactory::class, '__invoke')]
final class LoginMiddlewareFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsMiddleware(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                ['config', ['authentication' => [Configuration::POST_LOGIN_REDIRECT_KEY => '/dashboard']]],
                [MessageBusInterface::class, $this->createStub(MessageBusInterface::class)],
                [LoggerInterface::class, $this->createStub(LoggerInterface::class)],
            ]);

        self::assertInstanceOf(LoginMiddleware::class, (new LoginMiddlewareFactory())($container));
    }
}
