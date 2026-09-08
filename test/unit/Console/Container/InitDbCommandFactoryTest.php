<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Console\Container;

use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\UserManager\Console\Container\InitDbCommandFactory;
use Webware\UserManager\Console\InitDbCommand;

#[CoversClass(InitDbCommandFactory::class)]
final class InitDbCommandFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsCommandWithAdapter(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [AdapterInterface::class, $this->createStub(AdapterInterface::class)],
            ]);

        self::assertInstanceOf(InitDbCommand::class, (new InitDbCommandFactory())($container));
    }
}
