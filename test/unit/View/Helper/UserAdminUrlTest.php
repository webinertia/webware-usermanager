<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\View\Helper;

use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\View\Helper\UserAdminUrl;

#[CoversClass(UserAdminUrl::class)]
#[CoversMethod(UserAdminUrl::class, '__construct')]
#[CoversMethod(UserAdminUrl::class, '__invoke')]
#[CoversMethod(UserAdminUrl::class, 'resetState')]
final class UserAdminUrlTest extends TestCase
{
    #[Test]
    public function invokePrependsAdminPrefix(): void
    {
        $urlHelper = $this->createMock(UrlHelper::class);
        $urlHelper->expects($this->once())
            ->method('__invoke')
            ->with('admin.user.create', [], [], null, [])
            ->willReturn('/admin/user/create');

        $helper = new UserAdminUrl($urlHelper, 'admin.user.');

        self::assertSame('/admin/user/create', $helper('create'));
    }

    #[Test]
    public function invokeTrimsTrailingDotForEmptyRouteName(): void
    {
        $urlHelper = $this->createMock(UrlHelper::class);
        $urlHelper->expects($this->once())
            ->method('__invoke')
            ->with('admin.user', [], [], null, [])
            ->willReturn('/admin/user');

        $helper = new UserAdminUrl($urlHelper, 'admin.user.');

        self::assertSame('/admin/user', $helper(''));
    }

    #[Test]
    public function resetStateCompletesWithoutError(): void
    {
        $helper = new UserAdminUrl($this->createStub(UrlHelper::class), 'admin.user.');

        self::assertNull($helper->resetState());
    }
}
