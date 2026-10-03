<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Admin;

use Webware\MessageBus\Command\CommandResult;

/**
 * What the create-user middleware hands the request handler.
 *
 * The handler has exactly one render site for the form, so the middleware never
 * renders: it validates, dispatches, and leaves the outcome here.
 *
 * @internal
 */
final readonly class CreateUserState
{
    /**
     * @param list<string>                $assignableRoles
     * @param array<string, list<string>> $errors
     * @param array<string, string>       $old
     */
    public function __construct(
        public array $assignableRoles = [],
        public array $errors = [],
        public array $old = [],
        public ?CommandResult $result = null,
    ) {}
}
