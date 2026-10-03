<?php

declare(strict_types=1);

namespace Webware\UserManager\InputFilter;

use Laminas\Filter;
use Laminas\InputFilter;
use Laminas\InputFilter\Exception\ExceptionInterface;
use Laminas\Validator;
use Override;
use PhpDb\Validator\NoRecordExists;
use Webware\Core\InputFilter\SystemMessageTrait;
use Webware\UserManager\Validator\AssignableRoleValidator;

/**
 * The fields an administrator submits to create a user.
 *
 * There is no password field: the account is created inactive with an unusable random
 * hash and the user sets their own password from the verification email. `active` is
 * server-controlled and is not accepted here either.
 *
 * `AssignableRoleValidator` reads the candidate roles from the filter context, so the
 * caller must pass `assignableRoles` alongside the form data.
 *
 * @extends InputFilter\InputFilter<array{firstName: string, lastName: string, email: string, roleId: string}>
 */
final class CreateUserDataFilter extends InputFilter\InputFilter
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
     * a non-string unchanged, and without the guard a posted `email[]` reaches
     * `NoRecordExists`, which throws instead of reporting the field invalid.
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
                ['name' => Filter\StringToLower::class],
            ],
            'validators' => [
                self::stringGuard(self::EMAIL_MAX_LENGTH),
                ['name' => Validator\EmailAddress::class],
                [
                    'name'    => NoRecordExists::class,
                    'options' => [
                        'messages' => [
                            NoRecordExists::ERROR_RECORD_FOUND => 'An account with this email already exists.',
                        ],
                    ],
                ],
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
    }
}
