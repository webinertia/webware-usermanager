<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Listener\Container;

use Laminas\View\HelperPluginManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Core\UserInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Listener\Container\SendVerificationEmailListenerFactory;
use Webware\UserManager\Listener\SendVerificationEmailListener;
use WebwareTest\UserManager\Support\ViewHelperManagerTrait;

#[CoversClass(SendVerificationEmailListenerFactory::class)]
#[CoversMethod(SendVerificationEmailListenerFactory::class, '__invoke')]
final class SendVerificationEmailListenerFactoryTest extends TestCase
{
    use ViewHelperManagerTrait;

    #[Test]
    public function invokeBuildsListener(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')
            ->willReturnMap([
                [
                    'config',
                    [
                        UserInterface::class => [
                            'base_url'                   => 'https://example.com',
                            'verification_email_subject' => 'Verify your email',
                        ],
                    ],
                ],
                [MessageBusInterface::class, $this->createStub(MessageBusInterface::class)],
                [HelperPluginManager::class, $this->userUrlHelperManager()],
            ]);

        self::assertInstanceOf(
            SendVerificationEmailListener::class,
            (new SendVerificationEmailListenerFactory())($container),
        );
    }
}
