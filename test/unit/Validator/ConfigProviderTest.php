<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Validator;

use PhpDb\Validator\NoRecordExists;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Validator\AssignableRoleValidator;
use Webware\UserManager\Validator\ConfigProvider;
use Webware\UserManager\Validator\Container\NoRecordExistsFactory;

#[CoversClass(ConfigProvider::class)]
#[CoversMethod(ConfigProvider::class, '__invoke')]
final class ConfigProviderTest extends TestCase
{
    #[Test]
    public function providesTheUniquenessFactoryAndTheAssignableRoleValidator(): void
    {
        self::assertSame(
            [
                'validators' => [
                    'factories'  => [
                        NoRecordExists::class => NoRecordExistsFactory::class,
                    ],
                    'invokables' => [
                        AssignableRoleValidator::class => AssignableRoleValidator::class,
                    ],
                ],
            ],
            new ConfigProvider()(),
        );
    }
}
