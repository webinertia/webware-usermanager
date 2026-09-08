<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\QueryHandler\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\UserManager\QueryHandler\CheckUserActiveHandler;
use Webware\UserManager\QueryHandler\Container\CheckUserActiveHandlerFactory;
use Webware\UserManager\Repository\UserRepositoryInterface;

#[CoversClass(CheckUserActiveHandlerFactory::class)]
#[CoversMethod(CheckUserActiveHandlerFactory::class, '__invoke')]
final class CheckUserActiveHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturn($this->createStub(UserRepositoryInterface::class));

        self::assertInstanceOf(CheckUserActiveHandler::class, (new CheckUserActiveHandlerFactory())($container));
    }
}
