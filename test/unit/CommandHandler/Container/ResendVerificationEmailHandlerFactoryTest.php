<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\CommandHandler\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Mailer\MailerInterface;
use Webware\UserManager\CommandHandler\Container\ResendVerificationEmailHandlerFactory;
use Webware\UserManager\CommandHandler\ResendVerificationEmailHandler;

#[CoversClass(ResendVerificationEmailHandlerFactory::class)]
#[CoversMethod(ResendVerificationEmailHandlerFactory::class, '__invoke')]
final class ResendVerificationEmailHandlerFactoryTest extends TestCase
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
            ResendVerificationEmailHandler::class,
            (new ResendVerificationEmailHandlerFactory())($container),
        );
    }
}
