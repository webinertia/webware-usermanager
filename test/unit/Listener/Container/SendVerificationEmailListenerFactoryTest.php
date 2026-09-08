<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Listener\Container;

use Laminas\View\HelperPluginManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Mailer\MailerInterface;
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
                [MailerInterface::class, $this->createStub(MailerInterface::class)],
                [HelperPluginManager::class, $this->userUrlHelperManager()],
            ]);

        self::assertInstanceOf(
            SendVerificationEmailListener::class,
            (new SendVerificationEmailListenerFactory())($container),
        );
    }
}
