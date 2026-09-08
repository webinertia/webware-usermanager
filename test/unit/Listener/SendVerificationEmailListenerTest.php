<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Listener;

use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\MailerInterface;
use Webware\UserManager\Command\CreateUserCommand;
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
    public function buildsAndSendsVerificationEmail(): void
    {
        $adapter = $this->createStub(AdapterInterface::class);
        $adapter->method('from')->willReturnSelf();
        $adapter->method('to')->willReturnSelf();
        $adapter->method('subject')->willReturnSelf();
        $adapter->method('isHtml')->willReturnSelf();
        $adapter->method('body')->willReturnSelf();
        $adapter->method('altBody')->willReturnSelf();

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('getAdapter')->willReturn($adapter);
        $mailer->expects($this->once())->method('send')->willReturn(true);

        $listener = $this->listener(mailer: $mailer);

        $listener($this->event());
    }

    #[Test]
    public function doesNothingWhenMailerHasNoAdapter(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('getAdapter')->willReturn(null);
        $mailer->expects($this->never())->method('send');

        $listener = $this->listener(mailer: $mailer);

        $listener($this->event());
    }

    private function event(): SendVerificationEmailEvent
    {
        $command = new CreateUserCommand(
            firstName        : 'Jane',
            lastName         : 'Doe',
            passwordHash     : bin2hex(random_bytes(16)),
            email            : 'jane@example.com',
            roleId           : ['Member'],
            verificationToken: bin2hex(random_bytes(16)),
        );

        return new SendVerificationEmailEvent($command);
    }

    private function listener(?MailerInterface $mailer = null): SendVerificationEmailListener
    {
        $mailer ??= $this->createStub(MailerInterface::class);

        $urlHelper = $this->createStub(UrlHelper::class);
        $urlHelper->method('__invoke')->willReturn('/user/verify-email');

        return new SendVerificationEmailListener(
            mailer             : $mailer,
            fromEmail          : 'noreply@example.com',
            fromName           : 'Webware',
            baseUrl            : 'https://example.com',
            verificationSubject: 'Verify your email',
            userUrl            : new UserUrl($urlHelper, 'user.manager.'),
        );
    }
}
