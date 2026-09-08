<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\View\Helper;

use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\View\Helper\UserUrl;

use function bin2hex;
use function random_bytes;

#[CoversClass(UserUrl::class)]
#[CoversMethod(UserUrl::class, '__construct')]
#[CoversMethod(UserUrl::class, '__invoke')]
#[CoversMethod(UserUrl::class, 'resetState')]
final class UserUrlTest extends TestCase
{
    #[Test]
    public function invokePrependsRouteNamePrefix(): void
    {
        $token = bin2hex(random_bytes(16));

        $urlHelper = $this->createMock(UrlHelper::class);
        $urlHelper->expects($this->once())
            ->method('__invoke')
            ->with('user.verify.email.read', ['token' => $token], [], null, [])
            ->willReturn("/user/verify-email/{$token}");

        $helper = new UserUrl($urlHelper, 'user.');

        self::assertSame("/user/verify-email/{$token}", $helper('verify.email.read', ['token' => $token]));
    }

    #[Test]
    public function resetStateCompletesWithoutError(): void
    {
        $helper = new UserUrl($this->createStub(UrlHelper::class), 'user.');

        self::assertNull($helper->resetState());
    }
}
