<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Support;

use Laminas\ServiceManager\ServiceManager;
use Laminas\View\HelperPluginManager;
use Mezzio\Helper\UrlHelper;
use Webware\UserManager\View\Helper\UserUrl;

/**
 * Builds a real HelperPluginManager with a working UserUrl helper. Used by
 * factory tests since HelperPluginManager is final and cannot be doubled.
 */
trait ViewHelperManagerTrait
{
    private function userUrlHelperManager(): HelperPluginManager
    {
        $urlHelper = $this->createStub(UrlHelper::class);
        $urlHelper->method('__invoke')->willReturn('/login');

        $manager = new HelperPluginManager(new ServiceManager());
        $manager->setService(UserUrl::class, new UserUrl($urlHelper, 'user.'));

        return $manager;
    }
}
