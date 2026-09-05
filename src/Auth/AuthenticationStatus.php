<?php

declare(strict_types=1);

namespace Webware\UserManager\Auth;

enum AuthenticationStatus
{
    case Success;
    case InvalidCredentials;
    case NotActive;
}
