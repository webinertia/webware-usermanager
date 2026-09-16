<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\ExceptionInterface as PslTypeException;
use Psr\Container\ContainerInterface;
use Webware\UserManager\Http\Admin\RequestHandler\Container\UpdateUserHandlerFactory;
use Webware\UserManager\Http\Admin\RequestHandler\UpdateUserHandler;

#[CoversClass(UpdateUserHandlerFactory::class)]
#[CoversMethod(UpdateUserHandlerFactory::class, '__invoke')]
final class UpdateUserHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsHandler(): void
    {
        $template = $this->createStub(TemplateRendererInterface::class);

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $template],
            ]);

        self::assertInstanceOf(UpdateUserHandler::class, (new UpdateUserHandlerFactory())($container));
    }

    /**
     * The factory asserts the resolved service really is a template renderer
     * before handing it to the handler, so a wrong service is reported as a
     * assertion failure rather than surfacing later as a TypeError.
     */
    #[Test]
    public function invokeThrowsWhenTheResolvedServiceIsNotATemplateRenderer(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturn('not-a-renderer');

        $this->expectException(PslTypeException::class);

        (new UpdateUserHandlerFactory())($container);
    }
}
