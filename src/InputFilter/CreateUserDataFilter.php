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

    /**
     * @throws ExceptionInterface
     */
    #[Override]
    public function init(): void
    {
        $this->add([
            'name'     => 'firstName',
            'required' => true,
            'filters'  => [
                ['name' => Filter\StringTrim::class],
            ],
        ]);

        $this->add([
            'name'     => 'lastName',
            'required' => true,
            'filters'  => [
                ['name' => Filter\StringTrim::class],
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
                ['name' => Validator\EmailAddress::class],
                ['name' => NoRecordExists::class],
            ],
        ]);

        $this->add([
            'name'       => 'roleId',
            'required'   => true,
            'filters'    => [
                ['name' => Filter\StringTrim::class],
            ],
            'validators' => [
                ['name' => AssignableRoleValidator::class],
            ],
        ]);
    }
}
