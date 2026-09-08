<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Support;

use Laminas\Filter\FilterPluginManager;
use Laminas\InputFilter;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Validator\ConfigProvider as ValidatorConfigProvider;
use Laminas\Validator\ValidatorPluginManager;
use Webware\UserManager\InputFilter\RegistrationDataFilter;
use Webware\UserManager\InputFilter\UpdateUserDataFilter;

/**
 * Builds fully-wired, real Laminas input filters for unit tests, mirroring the
 * production `input_filters` wiring.
 */
final class InputFilterHelper
{
    public static function inputFilterPluginManager(): InputFilter\InputFilterPluginManager
    {
        $manager = new InputFilter\InputFilterPluginManager(new ServiceManager());
        $manager->setService(RegistrationDataFilter::class, self::registrationDataFilter());
        $manager->setService(UpdateUserDataFilter::class, self::updateUserDataFilter());

        return $manager;
    }

    public static function registrationDataFilter(): RegistrationDataFilter
    {
        $filter = new RegistrationDataFilter(self::factory());
        $filter->init();

        return $filter;
    }

    public static function updateUserDataFilter(): UpdateUserDataFilter
    {
        $filter = new UpdateUserDataFilter(self::factory());
        $filter->init();

        return $filter;
    }

    private static function factory(): InputFilter\Factory
    {
        $container = new ServiceManager(new ValidatorConfigProvider()->getDependencyConfig());

        $container->setService(FilterPluginManager::class, new FilterPluginManager($container));
        $container->setService(ValidatorPluginManager::class, new ValidatorPluginManager($container));
        $container->setService(
            InputFilter\InputFilterPluginManager::class,
            new InputFilter\InputFilterPluginManager($container),
        );

        return InputFilter\Factory::new($container);
    }
}
