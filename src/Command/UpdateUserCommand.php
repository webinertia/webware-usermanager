<?php

declare(strict_types=1);

namespace Webware\UserManager\Command;

use Webware\MessageBus\Command\NamedCommandInterface;
use Webware\MessageBus\Command\NamedCommandTrait;

readonly class UpdateUserCommand implements NamedCommandInterface
{
    use NamedCommandTrait;

    /** @param string[] $roleId */
    public function __construct(
        public string|int $id,
        public string $firstName,
        public string $lastName,
        public string $email,
        public array $roleId,
        public bool $active,
    ) {}
}
