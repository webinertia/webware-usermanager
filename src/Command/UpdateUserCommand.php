<?php

declare(strict_types=1);

namespace Webware\UserManager\Command;

use Webware\MessageBus\Command\NamedCommandInterface;
use Webware\MessageBus\Command\NamedCommandTrait;

final class UpdateUserCommand implements NamedCommandInterface
{
    use NamedCommandTrait;

    /** @param string $roleId */
    // @mago-expect lint:excessive-parameter-list - accepted: the promoted properties are the row shape; splitting the list changes every call site.
    public function __construct(
        public readonly string|int $id,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly string $roleId,
        public readonly bool $active,
    ) {}
}
