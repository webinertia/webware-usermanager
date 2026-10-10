<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Admin\RequestHandler\Container;

use Laminas\ServiceManager\ServiceManager;
use Laminas\View\HelperPluginManager;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use Webware\UserManager\Http\Admin\RequestHandler\Container\UpdateUserHandlerFactory;
use Webware\UserManager\Http\Admin\RequestHandler\UpdateUserHandler;
use Webware\UserManager\Http\RequestHandler\UserListHandler;
use Webware\UserManager\View\Helper\UserAdminUrl;

#[CoversClass(UpdateUserHandlerFactory::class)]
#[CoversMethod(UpdateUserHandlerFactory::class, '__invoke')]
final class UpdateUserHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsHandlerWithTheListHandlerAndListUrl(): void
    {
        $urlHelper = $this->createStub(UrlHelper::class);
        $urlHelper->method('__invoke')->willReturn('/backoffice/user');

        $helperManager = new HelperPluginManager(new ServiceManager());
        $helperManager->setService(
            UserAdminUrl::class,
            new UserAdminUrl(
                urlHelper      : $urlHelper,
                routeNamePrefix: 'backoffice.user.',
            ),
        );

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [HelperPluginManager::class, $helperManager],
                [
                    UserListHandler::class,
                    new UserListHandler(
                        template: $this->createStub(TemplateRendererInterface::class),
                    ),
                ],
            ]);

        $handler = new UpdateUserHandlerFactory()($container);

        self::assertInstanceOf(UpdateUserHandler::class, $handler);
        self::assertSame(
            '/backoffice/user',
            new ReflectionProperty(UpdateUserHandler::class, 'listUrl')->getValue($handler),
        );
    }
}
