<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\QueryHandler\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Core\AclInterface;
use Webware\UserManager\QueryHandler\Container\FetchAssignableRolesHandlerFactory;
use Webware\UserManager\QueryHandler\FetchAssignableRolesHandler;

#[CoversClass(FetchAssignableRolesHandlerFactory::class)]
#[CoversMethod(FetchAssignableRolesHandlerFactory::class, '__invoke')]
final class FetchAssignableRolesHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturn($this->createStub(AclInterface::class));

        self::assertInstanceOf(
            FetchAssignableRolesHandler::class,
            new FetchAssignableRolesHandlerFactory()($container),
        );
    }
}
