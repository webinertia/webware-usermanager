<?php

declare(strict_types=1);

namespace Webware\UserManager\Auth;

use Webware\Core\UserInterface;
use Webware\UserManager\Entity\User;

final readonly class AuthenticationResult
{
    public function __construct(
        public AuthenticationStatus $status,
        public (UserInterface&User)|null $user = null,
    ) {}
}
