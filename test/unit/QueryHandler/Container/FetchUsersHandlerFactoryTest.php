<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\QueryHandler\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\UserManager\QueryHandler\Container\FetchUsersHandlerFactory;
use Webware\UserManager\QueryHandler\FetchUsersHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

#[CoversClass(FetchUsersHandlerFactory::class)]
#[CoversMethod(FetchUsersHandlerFactory::class, '__invoke')]
final class FetchUsersHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturn($this->createStub(UserRepositoryInterface::class));

        self::assertInstanceOf(FetchUsersHandler::class, (new FetchUsersHandlerFactory())($container));
    }
}
