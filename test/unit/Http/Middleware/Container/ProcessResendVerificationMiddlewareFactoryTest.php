<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\Middleware\Container;

use Laminas\View\HelperPluginManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Mailer\MailerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Http\Middleware\Container\ProcessResendVerificationMiddlewareFactory;
use Webware\UserManager\Http\Middleware\ProcessResendVerificationMiddleware;
use WebwareTest\UserManager\Support\ViewHelperManagerTrait;

#[CoversClass(ProcessResendVerificationMiddlewareFactory::class)]
#[CoversMethod(ProcessResendVerificationMiddlewareFactory::class, '__invoke')]
final class ProcessResendVerificationMiddlewareFactoryTest extends TestCase
{
    use ViewHelperManagerTrait;

    #[Test]
    public function invokeBuildsMiddleware(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [
                    'config',
                    [
                        'user'                 => [
                            'from_email' => 'noreply@example.com',
                            'from_name'  => 'Example',
                            'base_url'   => 'http://localhost',
                        ],
                        MailerInterface::class => ['verification_email_subject' => 'Verify'],
                    ],
                ],
                [MessageBusInterface::class, $this->createStub(MessageBusInterface::class)],
                [MailerInterface::class, $this->createStub(MailerInterface::class)],
                [HelperPluginManager::class, $this->userUrlHelperManager()],
            ]);

        static::assertInstanceOf(
            ProcessResendVerificationMiddleware::class,
            (new ProcessResendVerificationMiddlewareFactory())($container),
        );
    }
}
