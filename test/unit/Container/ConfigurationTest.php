<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Core\Exception\ContainerException;
use Webware\Core\UserInterface;
use Webware\UserManager\Container\Configuration;

#[CoversClass(Configuration::class)]
final class ConfigurationTest extends TestCase
{
    #[Test]
    public function exposesConfigConstants(): void
    {
        self::assertSame(UserInterface::class, Configuration::CONFIG_KEY);
        self::assertSame('user.manager', Configuration::ROUTE_SEGMENT_VALUE);
        self::assertSame('user.manager.', Configuration::ROUTE_NAME_PREFIX_VALUE);
    }

    #[Test]
    public function returnsAuthenticationConfigWhenPresent(): void
    {
        self::assertSame(
            ['username' => 'email'],
            Configuration::getCredentialConfig($this->containerWith(['authentication' => [
                'username' => 'email',
            ]]), 'TestFactory'),
        );
    }

    #[Test]
    public function throwsWhenAuthenticationConfigEmpty(): void
    {
        $this->expectException(ContainerException::class);

        Configuration::getCredentialConfig($this->containerWith(['authentication' => []]), 'TestFactory');
    }

    #[Test]
    public function throwsWhenAuthenticationConfigNotArray(): void
    {
        $this->expectException(ContainerException::class);

        Configuration::getCredentialConfig($this->containerWith(['authentication' => 'nope']), 'TestFactory');
    }

    #[Test]
    public function throwsWhenAuthenticationKeyMissing(): void
    {
        $this->expectException(ContainerException::class);

        Configuration::getCredentialConfig($this->containerWith([]), 'TestFactory');
    }

    /**
     * @param array<string, mixed> $config
     */
    private function containerWith(array $config): ContainerInterface
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturnMap([['config', $config]]);

        return $container;
    }
}
