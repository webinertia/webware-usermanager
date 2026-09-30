<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\View\Helper;

use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use Webware\Admin\AdminInterface;
use Webware\Admin\Container\Configuration as AdminConfiguration;
use Webware\UserManager\View\Helper\UserAdminUrl;
use Webware\UserManager\View\Helper\UserAdminUrlFactory;

#[CoversClass(UserAdminUrlFactory::class)]
#[CoversMethod(UserAdminUrlFactory::class, '__invoke')]
final class UserAdminUrlFactoryTest extends TestCase
{
    #[Test]
    public function invokeCombinesAdminAndModulePrefixes(): void
    {
        $urlHelper = $this->createStub(UrlHelper::class);

        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnMap([['config', true]]);
        $container->method('get')
            ->willReturnMap([
                [UrlHelper::class, $urlHelper],
                [
                    'config',
                    [
                        AdminInterface::class => [AdminConfiguration::ADMIN_NAME_KEY => 'backoffice'],
                    ],
                ],
            ]);

        $helper = (new UserAdminUrlFactory())($container);

        self::assertInstanceOf(UserAdminUrl::class, $helper);
        self::assertSame('backoffice.user.', $this->routeNamePrefix($helper));
    }

    private function routeNamePrefix(UserAdminUrl $helper): string
    {
        return new ReflectionProperty(UserAdminUrl::class, 'routeNamePrefix')->getValue($helper);
    }
}
