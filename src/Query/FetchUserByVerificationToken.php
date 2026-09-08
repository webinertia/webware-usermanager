<?php

declare(strict_types=1);

namespace Webware\UserManager\Query;

use SensitiveParameter;
use Webware\MessageBus\Query\QueryInterface;

/**
 * Fetch a user by verification token.
 */
final readonly class FetchUserByVerificationToken implements QueryInterface
{
    public function __construct(
        #[SensitiveParameter]
        public string $token,
    ) {}
}
