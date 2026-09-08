<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\CommandHandler\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\UserManager\CommandHandler\Container\ToggleUserActiveHandlerFactory;
use Webware\UserManager\CommandHandler\ToggleUserActiveHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

#[CoversClass(ToggleUserActiveHandlerFactory::class)]
#[CoversMethod(ToggleUserActiveHandlerFactory::class, '__invoke')]
final class ToggleUserActiveHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [UserRepositoryInterface::class, $this->createStub(UserRepositoryInterface::class)],
            ]);

        self::assertInstanceOf(ToggleUserActiveHandler::class, (new ToggleUserActiveHandlerFactory())($container));
    }
}
