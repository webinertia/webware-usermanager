<?php

declare(strict_types=1);

namespace Webware\UserManager\Exception;

use UnexpectedValueException as SplUnexpectedValueException;

/**
 * Thrown when a user identity is requested before an email address has been assigned.
 *
 * The identity contract returns a string, so an entity that was constructed but never
 * hydrated from a row has no identity to report. Reading one is a usage error, not a
 * recoverable state.
 *
 * @api
 */
final class UnassignedIdentityException extends SplUnexpectedValueException implements ExceptionInterface {}
