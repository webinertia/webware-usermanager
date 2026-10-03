<?php

declare(strict_types=1);

namespace Webware\UserManager\InputFilter;

use Laminas\InputFilter;
use Laminas\Validator;
use Override;

/**
 * The set-password form: the plaintext goes in under `passwordHash`, matching
 * {@see \Webware\UserManager\Command\SetPasswordCommand}, which hashes it.
 *
 * `StringLength` runs first and breaks the chain, so a posted array never
 * reaches `Identical` (see the note in the create-user filter).
 *
 * @extends InputFilter\InputFilter<array{passwordHash: string, confirmPasswordHash: string}>
 */
final class SetPasswordDataFilter extends InputFilter\InputFilter
{
    public const int MINIMUM_LENGTH = 12;

    /**
     * @throws InputFilter\Exception\ExceptionInterface
     */
    #[Override]
    public function init(): void
    {
        $this->add([
            'name'       => 'passwordHash',
            'required'   => true,
            'validators' => [
                [
                    'name'                   => Validator\StringLength::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'min'      => self::MINIMUM_LENGTH,
                        'max'      => 255,
                        'messages' => [
                            Validator\StringLength::TOO_SHORT =>
                                'Your password must be at least ' . self::MINIMUM_LENGTH . ' characters long.',
                        ],
                    ],
                ],
            ],
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
    }
}
