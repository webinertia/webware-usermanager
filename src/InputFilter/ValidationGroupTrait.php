<?php

declare(strict_types=1);

namespace Webware\UserManager\InputFilter;

trait ValidationGroupTrait
{
    public const array REGISTRATION_VALIDATION_GROUP = [
        'firstName',
        'lastName',
        'email',
        'passwordHash',
        'confirmPasswordHash',
        'verificationToken',
        'roleId',
        'active',
    ];

    public const array UPDATE_VALIDATION_GROUP = [
        'id',
        'firstName',
        'lastName',
        'email',
        'roleId',
        'active',
    ];
}
