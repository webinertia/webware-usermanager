<?php

declare(strict_types=1);

namespace Webware\UserManager\Validator;

use Laminas\Validator\AbstractValidator;
use Override;

use function in_array;
use function is_array;
use function is_string;

/**
 * Requires the value to be one of the role IDs the actor may assign.
 *
 * The candidate roles arrive as input-filter context under `assignableRoles`, so the
 * client's own list is never trusted: a missing or empty list rejects every value.
 */
final class AssignableRoleValidator extends AbstractValidator
{
    public const string NOT_ASSIGNABLE = 'notAssignable';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::NOT_ASSIGNABLE => 'The selected role is not one you may assign.',
    ];

    /**
     * @param array<string, mixed> $context
     */
    #[Override]
    public function isValid(mixed $value, array $context = []): bool
    {
        $this->setValue($value);

        $isAssignable =
            is_string($value)
            && is_array($context['assignableRoles'] ?? null)
            && in_array(
                needle  : $value,
                haystack: $context['assignableRoles'],
                strict  : true,
            );

        if (! $isAssignable) {
            $this->error(messageKey: self::NOT_ASSIGNABLE);

            return false;
        }

        return true;
    }
}
