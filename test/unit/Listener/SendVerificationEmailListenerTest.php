<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Listener;

use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Command\CreateUserCommand;
use Webware\UserManager\Command\SendVerificationEmailCommand;
use Webware\UserManager\Event\SendVerificationEmailEvent;
use Webware\UserManager\Listener\SendVerificationEmailListener;
use Webware\UserManager\View\Helper\UserUrl;

use function bin2hex;
use function random_bytes;

#[CoversClass(SendVerificationEmailListener::class)]
#[CoversMethod(SendVerificationEmailListener::class, '__construct')]
#[CoversMethod(SendVerificationEmailListener::class, '__invoke')]
final class SendVerificationEmailListenerTest extends TestCase
{
    #[Test]
    public function dispatchesSendVerificationEmailCommand(): void
    {
        $dispatched = null;
        $result     = $this->createStub(CommandResultInterface::class);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())
            ->method('handle')
            ->willReturnCallback(static function (object $message) use (&$dispatched, $result): CommandResultInterface {
                $dispatched = $message;

                return $result;
            });

        $listener = $this->listener(messageBus: $messageBus);

        $listener($this->event());

        if (! $dispatched instanceof SendVerificationEmailCommand) {
            static::fail('Expected a SendVerificationEmailCommand to be dispatched.');
        }

        static::assertSame('jane@example.com', $dispatched->to);
        static::assertSame('Jane', $dispatched->firstName);
        static::assertSame('https://example.com/user/verify-email', $dispatched->verificationUrl);
        static::assertSame('Verify your email', $dispatched->subject);
    }

    private function event(): SendVerificationEmailEvent
    {
        $command = new CreateUserCommand(
            firstName        : 'Jane',
            lastName         : 'Doe',
            passwordHash     : bin2hex(random_bytes(16)),
            email            : 'jane@example.com',
            roleId           : 'Member',
            verificationToken: bin2hex(random_bytes(16)),
        );

        return new SendVerificationEmailEvent($command);
    }

    private function listener(?MessageBusInterface $messageBus = null): SendVerificationEmailListener
    {
        $messageBus ??= $this->createStub(MessageBusInterface::class);

        $urlHelper = $this->createStub(UrlHelper::class);
        $urlHelper->method('__invoke')->willReturn('/user/verify-email');

        return new SendVerificationEmailListener(
            messageBus         : $messageBus,
            baseUrl            : 'https://example.com',
            verificationSubject: 'Verify your email',
            userUrl            : new UserUrl($urlHelper, 'user.manager.'),
        );
    }
}
