<?php

declare(strict_types=1);

namespace Webware\UserManager\Query;

use SensitiveParameter;
use Webware\MessageBus\Query\QueryInterface;

/**
 * Authenticate a user by credential and password.
 */
final readonly class AuthenticateUser implements QueryInterface
{
    public function __construct(
        public string $credential,
        #[SensitiveParameter]
        public ?string $password = null,
    ) {}
}
