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
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\Admin\RequestHandler\Container\CreateUserHandlerFactory;
use Webware\UserManager\Http\Admin\RequestHandler\CreateUserHandler;
use Webware\UserManager\Http\RequestHandler\UserListHandler;
use Webware\UserManager\View\Helper\UserAdminUrl;

#[CoversClass(CreateUserHandlerFactory::class)]
#[CoversMethod(CreateUserHandlerFactory::class, '__invoke')]
final class CreateUserHandlerFactoryTest extends TestCase
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
                [TemplateRendererInterface::class, $this->createStub(TemplateRendererInterface::class)],
                [
                    UserListHandler::class,
                    new UserListHandler(
                        template  : $this->createStub(TemplateRendererInterface::class),
                        messageBus: $this->createStub(MessageBusInterface::class),
                    ),
                ],
            ]);

        $handler = (new CreateUserHandlerFactory())($container);

        self::assertInstanceOf(CreateUserHandler::class, $handler);
        self::assertSame(
            '/backoffice/user',
            new ReflectionProperty(CreateUserHandler::class, 'listUrl')->getValue($handler),
        );
    }
}
