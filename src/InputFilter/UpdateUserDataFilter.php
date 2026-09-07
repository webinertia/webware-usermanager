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
 * @extends InputFilter\InputFilter<array{id: int, firstName: string, lastName: string, email: string, roleId: array<string>, active: bool}>
 */
final class UpdateUserDataFilter extends InputFilter\InputFilter
{
    use SystemMessageTrait;

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
