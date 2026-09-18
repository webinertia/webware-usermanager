<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Command\CreateUserCommand;
use Webware\UserManager\Event\SendVerificationEmailEvent;

use function bin2hex;
use function random_bytes;

#[CoversClass(SendVerificationEmailEvent::class)]
#[CoversMethod(SendVerificationEmailEvent::class, '__construct')]
#[CoversMethod(SendVerificationEmailEvent::class, 'getEmail')]
#[CoversMethod(SendVerificationEmailEvent::class, 'getTarget')]
#[CoversMethod(SendVerificationEmailEvent::class, 'getToken')]
final class SendVerificationEmailEventTest extends TestCase
{
    #[Test]
    public function exposesTargetDetails(): void
    {
        $token   = bin2hex(random_bytes(16));
        $command = new CreateUserCommand(
            firstName        : 'Jane',
            lastName         : 'Doe',
            passwordHash     : bin2hex(random_bytes(16)),
            email            : 'jane@example.com',
            roleId           : 'Member',
            verificationToken: $token,
        );

        $event = new SendVerificationEmailEvent($command);

        self::assertSame($command, $event->getTarget());
        self::assertSame('jane@example.com', $event->getEmail());
        self::assertSame($token, $event->getToken());
    }
}
