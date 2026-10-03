<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Support;

use Laminas\Filter\FilterPluginManager;
use Laminas\InputFilter;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Validator\ConfigProvider as ValidatorConfigProvider;
use Laminas\Validator\ValidatorInterface;
use Laminas\Validator\ValidatorPluginManager;
use Override;
use PhpDb\Validator\NoRecordExists;
use Webware\UserManager\InputFilter\CreateUserDataFilter;
use Webware\UserManager\InputFilter\RegistrationDataFilter;
use Webware\UserManager\InputFilter\SetPasswordDataFilter;
use Webware\UserManager\InputFilter\UpdateUserDataFilter;
use Webware\UserManager\Validator\AssignableRoleValidator;

/**
 * Builds fully-wired, real Laminas input filters for unit tests, mirroring the
 * production `input_filters` wiring.
 */
final class InputFilterHelper
{
    public static function createUserDataFilter(): CreateUserDataFilter
    {
        $filter = new CreateUserDataFilter(self::factory());
        $filter->init();

        return $filter;
    }

    public static function inputFilterPluginManager(): InputFilter\InputFilterPluginManager
    {
        $manager = new InputFilter\InputFilterPluginManager(new ServiceManager());
        $manager->setService(RegistrationDataFilter::class, self::registrationDataFilter());
        $manager->setService(UpdateUserDataFilter::class, self::updateUserDataFilter());
        $manager->setService(CreateUserDataFilter::class, self::createUserDataFilter());
        $manager->setService(SetPasswordDataFilter::class, self::setPasswordDataFilter());

        return $manager;
    }

    public static function registrationDataFilter(): RegistrationDataFilter
    {
        $filter = new RegistrationDataFilter(self::factory());
        $filter->init();

        return $filter;
    }

    public static function setPasswordDataFilter(): SetPasswordDataFilter
    {
        $filter = new SetPasswordDataFilter(self::factory());
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

        $validators = new ValidatorPluginManager($container);

        // NoRecordExists needs the container's database adapter, so a permissive
        // stand-in takes its place here; CreateUserDataFilterTest wires the real
        // validator for the duplicate-email path.
        $validators->setService(NoRecordExists::class, self::passingValidator());
        $validators->setService(AssignableRoleValidator::class, new AssignableRoleValidator());

        $container->setService(FilterPluginManager::class, new FilterPluginManager($container));
        $container->setService(ValidatorPluginManager::class, $validators);
        $container->setService(
            InputFilter\InputFilterPluginManager::class,
            new InputFilter\InputFilterPluginManager($container),
        );

        return InputFilter\Factory::new($container);
    }

    private static function passingValidator(): ValidatorInterface
    {
        return new class implements ValidatorInterface {
            #[Override]
            public function getMessages(): array
            {
                return [];
            }

            #[Override]
            public function isValid(mixed $value): bool
            {
                return true;
            }
        };
    }
}
