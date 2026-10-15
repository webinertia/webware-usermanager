<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use Webware\UserManager\Http\RequestHandler\Container\LoginHandlerFactory;
use Webware\UserManager\Http\RequestHandler\LoginHandler;

#[CoversClass(LoginHandlerFactory::class)]
#[CoversMethod(LoginHandlerFactory::class, '__invoke')]
final class LoginHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsHandler(): void
    {
        self::assertInstanceOf(
            LoginHandler::class,
            new LoginHandlerFactory()($this->containerWith([
                'authentication' => ['username' => 'email'],
            ])),
        );
    }

    #[Test]
    public function invokePassesTheConfiguredPostBodyFieldNamesToTheHandler(): void
    {
        $credentialField = 'user_name';
        $passwordField   = 'pass';

        $handler = new LoginHandlerFactory()($this->containerWith([
            'authentication' => ['username' => $credentialField, 'password' => $passwordField],
        ]));

        self::assertSame(
            $credentialField,
            new ReflectionProperty(LoginHandler::class, 'credentialField')->getValue($handler),
        );
        self::assertSame(
            $passwordField,
            new ReflectionProperty(LoginHandler::class, 'passwordField')->getValue($handler),
        );
    }

    /** @param array<string, mixed> $config */
    private function containerWith(array $config): ContainerInterface
    {
        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap([
                ['config', $config],
                [TemplateRendererInterface::class, $this->createStub(TemplateRendererInterface::class)],
            ]);

        return $container;
    }
}
