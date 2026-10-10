<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\CommandHandler\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\UserManager\CommandHandler\Container\SetPasswordHandlerFactory;
use Webware\UserManager\CommandHandler\SetPasswordHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

#[CoversClass(SetPasswordHandlerFactory::class)]
#[CoversMethod(SetPasswordHandlerFactory::class, '__invoke')]
final class SetPasswordHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsTheHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [UserRepositoryInterface::class, $this->createStub(UserRepositoryInterface::class)],
            ]);

        static::assertInstanceOf(
            SetPasswordHandler::class,
            new SetPasswordHandlerFactory()($container),
        );
    }
}
