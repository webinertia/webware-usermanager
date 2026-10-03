<?php

declare(strict_types=1);

namespace Webware\UserManager\InputFilter;

use Laminas\Filter;
use Laminas\InputFilter;
use Laminas\InputFilter\Exception\ExceptionInterface;
use Laminas\Validator;
use Override;
use Webware\Core\InputFilter\SystemMessageTrait;
use Webware\UserManager\Validator\AssignableRoleValidator;

/**
 * @extends InputFilter\InputFilter<array{id: int, firstName: string, lastName: string, email: string, roleId: string, active: bool}>
 */
final class UpdateUserDataFilter extends InputFilter\InputFilter
{
    use SystemMessageTrait;

    /** Column sizes, mirroring the schema in {@see \Webware\UserManager\Console\Schema\UserSchema::userTable()}. */
    private const int MIN_LENGTH = 1;

    private const int FIRST_NAME_MAX_LENGTH = 75;

    private const int LAST_NAME_MAX_LENGTH = 75;

    private const int EMAIL_MAX_LENGTH = 255;

    private const int ROLE_ID_MAX_LENGTH = 50;

    /**
     * Rejects a non-string before any later validator can touch it: `StringTrim` returns
     * a non-string unchanged and `NotEmpty` accepts a non-empty array, so without this
     * guard a posted `roleId[]` reaches the command as an array.
     *
     * @return array{name: class-string, break_chain_on_failure: true, options: array{min: int, max: int}}
     */
    private static function stringGuard(int $max): array
    {
        return [
            'name'                   => Validator\StringLength::class,
            'break_chain_on_failure' => true,
            'options'                => ['min' => self::MIN_LENGTH, 'max' => $max],
        ];
    }

    /**
     * @throws ExceptionInterface
     */
    #[Override]
    public function init(): void
    {
        $this->add([
            'name'        => 'id',
            'allow_empty' => true,
            'filters'     => [
                ['name' => Filter\ToInt::class],
                ['name' => Filter\ToNull::class],
            ],
        ]);

        $this->add([
            'name'       => 'firstName',
            'required'   => true,
            'filters'    => [
                ['name' => Filter\StringTrim::class],
            ],
            'validators' => [
                self::stringGuard(self::FIRST_NAME_MAX_LENGTH),
            ],
        ]);

        $this->add([
            'name'       => 'lastName',
            'required'   => true,
            'filters'    => [
                ['name' => Filter\StringTrim::class],
            ],
            'validators' => [
                self::stringGuard(self::LAST_NAME_MAX_LENGTH),
            ],
        ]);

        $this->add([
            'name'       => 'email',
            'required'   => true,
            'filters'    => [
                ['name' => Filter\StringTrim::class],
            ],
            'validators' => [
                self::stringGuard(self::EMAIL_MAX_LENGTH),
                ['name' => Validator\EmailAddress::class],
            ],
        ]);

        $this->add([
            'name'       => 'roleId',
            'required'   => true,
            'filters'    => [
                ['name' => Filter\StringTrim::class],
            ],
            'validators' => [
                self::stringGuard(self::ROLE_ID_MAX_LENGTH),
                ['name' => AssignableRoleValidator::class],
            ],
        ]);

        $this->add([
            'name'           => 'active',
            'fallback_value' => false,
            'filters'        => [
                ['name' => Filter\Boolean::class],
            ],
        ]);
    }
}
