<?php

declare(strict_types=1);

namespace Webware\UserManager\Validator;

use PhpDb\Validator\NoRecordExists;
use Webware\UserManager\Validator\Container\NoRecordExistsFactory;

/**
 * Validator wiring for the package.
 *
 * This lives here rather than in the package root provider because the architectural guard
 * allows `PhpDb\Validator\**` only from `*\InputFilter\**` and `*\Validator\**`, and the
 * plugin-manager key for the uniqueness validator is a `PhpDb\Validator` class name. The
 * root provider merges this section.
 *
 * @type ValidatorConfig = array{
 *     factories: array<class-string, class-string>,
 *     invokables: array<class-string, class-string>
 * }
 *
 * @internal
 */
final class ConfigProvider
{
    /**
     * @return array{validators: ValidatorConfig}
     */
    public function __invoke(): array
    {
        return [
            'validators' => [
                'factories'  => [
                    NoRecordExists::class => NoRecordExistsFactory::class,
                ],
                'invokables' => [
                    AssignableRoleValidator::class => AssignableRoleValidator::class,
                ],
            ],
        ];
    }
}
