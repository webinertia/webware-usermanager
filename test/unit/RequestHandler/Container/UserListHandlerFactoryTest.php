<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\UserManager\Repository\UserRepositoryInterface;
use Webware\UserManager\RequestHandler\Container\UserListHandlerFactory;
use Webware\UserManager\RequestHandler\UserListHandler;

#[CoversClass(UserListHandlerFactory::class)]
#[CoversMethod(UserListHandlerFactory::class, '__invoke')]
final class UserListHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $this->createStub(TemplateRendererInterface::class)],
                [UserRepositoryInterface::class, $this->createStub(UserRepositoryInterface::class)],
            ]);

        self::assertInstanceOf(UserListHandler::class, (new UserListHandlerFactory())($container));
    }
}
