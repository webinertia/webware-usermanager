<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Middleware\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Webware\UserManager\Container\Configuration;
use Webware\UserManager\Middleware\Container\LoginMiddlewareFactory;
use Webware\UserManager\Middleware\LoginMiddleware;
use Webware\UserManager\Repository\UserRepositoryInterface;

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
                [UserRepositoryInterface::class, $this->createStub(UserRepositoryInterface::class)],
                [LoggerInterface::class, $this->createStub(LoggerInterface::class)],
            ]);

        self::assertInstanceOf(LoginMiddleware::class, (new LoginMiddlewareFactory())($container));
    }
}
