<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\CommandHandler\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Mailer\MailerInterface;
use Webware\UserManager\CommandHandler\Container\SendVerificationEmailHandlerFactory;
use Webware\UserManager\CommandHandler\SendVerificationEmailHandler;

#[CoversClass(SendVerificationEmailHandlerFactory::class)]
#[CoversMethod(SendVerificationEmailHandlerFactory::class, '__invoke')]
final class SendVerificationEmailHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [MailerInterface::class, $this->createStub(MailerInterface::class)],
            ]);

        self::assertInstanceOf(
            SendVerificationEmailHandler::class,
            (new SendVerificationEmailHandlerFactory())($container),
        );
    }
}
