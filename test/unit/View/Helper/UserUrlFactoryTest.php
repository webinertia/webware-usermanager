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
use Webware\Core\UserInterface;
use Webware\UserManager\View\Helper\UserUrl;
use Webware\UserManager\View\Helper\UserUrlFactory;

#[CoversClass(UserUrlFactory::class)]
#[CoversMethod(UserUrlFactory::class, '__invoke')]
final class UserUrlFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsHelperWithRoutePrefix(): void
    {
        $urlHelper = $this->createStub(UrlHelper::class);

        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnMap([['config', true]]);
        $container->method('get')
            ->willReturnMap([
                [UrlHelper::class, $urlHelper],
                ['config', [UserInterface::class => ['route_name_prefix' => 'user.manager.']]],
            ]);

        $helper = (new UserUrlFactory())($container);

        self::assertInstanceOf(UserUrl::class, $helper);
        self::assertSame('user.manager.', $this->routeNamePrefix($helper));
    }

    private function routeNamePrefix(UserUrl $helper): string
    {
        return new ReflectionProperty(UserUrl::class, 'routeNamePrefix')->getValue($helper);
    }
}
