<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\RequestHandler\Container;

use Laminas\View\HelperPluginManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Mailer\MailerInterface;
use Webware\UserManager\Repository\UserRepositoryInterface;
use Webware\UserManager\RequestHandler\Container\ResendVerificationHandlerFactory;
use Webware\UserManager\RequestHandler\ResendVerificationHandler;
use WebwareTest\UserManager\Support\ViewHelperManagerTrait;

#[CoversClass(ResendVerificationHandlerFactory::class)]
#[CoversMethod(ResendVerificationHandlerFactory::class, '__invoke')]
final class ResendVerificationHandlerFactoryTest extends TestCase
{
    use ViewHelperManagerTrait;

    #[Test]
    public function invokeBuildsHandler(): void
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
                [TemplateRendererInterface::class, $this->createStub(TemplateRendererInterface::class)],
                [UserRepositoryInterface::class, $this->createStub(UserRepositoryInterface::class)],
                [MailerInterface::class, $this->createStub(MailerInterface::class)],
                [HelperPluginManager::class, $this->userUrlHelperManager()],
            ]);

        self::assertInstanceOf(ResendVerificationHandler::class, (new ResendVerificationHandlerFactory())($container));
    }
}
