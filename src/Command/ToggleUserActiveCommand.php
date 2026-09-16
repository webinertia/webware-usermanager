<?php

declare(strict_types=1);

namespace Webware\UserManager\Command;

use Webware\MessageBus\Command\NamedCommandInterface;
use Webware\MessageBus\Command\NamedCommandTrait;

final class ToggleUserActiveCommand implements NamedCommandInterface
{
    use NamedCommandTrait;

    public function __construct(
        public readonly int $id,
    ) {}
}
