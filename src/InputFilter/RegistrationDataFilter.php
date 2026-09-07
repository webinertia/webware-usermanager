<?php

declare(strict_types=1);

namespace Webware\UserManager\InputFilter;

use Laminas\Filter;
use Laminas\InputFilter;
use Laminas\InputFilter\Exception\ExceptionInterface;
use Laminas\Validator;
use Override;
use Webware\Core\InputFilter\SystemMessageTrait;

/**
 * @extends InputFilter\InputFilter<array{firstName: string, lastName: string, email: string, passwordHash: string, confirmPasswordHash: string, verificationToken: string|null, roleId: string, active: bool}>
 */
final class RegistrationDataFilter extends InputFilter\InputFilter
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
            ],
            'validators' => [
                ['name' => Validator\EmailAddress::class],
            ],
        ]);

        $this->add([
            'name'     => 'passwordHash',
            'required' => true,
        ]);

        $this->add([
            'name'       => 'confirmPasswordHash',
            'required'   => true,
            'validators' => [
                [
                    'name'    => Validator\Identical::class,
                    'options' => [
                        /** @mago-expect lint:no-literal-password */
                        'token'    => 'passwordHash',
                        'messages' => [
                            Validator\Identical::NOT_SAME      => 'Passwords do not match.',
                            Validator\Identical::MISSING_TOKEN => 'Please enter your password.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'        => 'verificationToken',
            'allow_empty' => true,
            'validators'  => [
                ['name' => Validator\Uuid::class],
            ],
        ]);

        $this->add([
            'name'     => 'roleId',
            'required' => true,
            'filters'  => [
                ['name' => Filter\StringTrim::class],
            ],
        ]);

        $this->add([
            'name'              => 'active',
            'allow_empty'       => true,
            'continue_if_empty' => true,
            'required'          => false,
            'fallback_value'    => false,
            'filters'           => [
                ['name' => Filter\Boolean::class],
            ],
        ]);
    }
}
