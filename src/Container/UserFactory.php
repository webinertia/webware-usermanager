<?php

declare(strict_types=1);

namespace Webware\UserManager\Container;

use PhpDb\ResultSet\RowPrototypeInterface;
use Psl\Type;
use Psr\Container\ContainerInterface;
use Webware\Core\UserInterface;

/**
 * DI factory for the UserInterface::class callable service.
 *
 * Returns a callable that creates a UserInterface implementation from an array of data.
 * The callable is used by IdentityMiddleware to reconstruct the authenticated user from session data.
 */
final class UserFactory
{
    public function __invoke(ContainerInterface $container): callable
    {
        $prototype = $container->get(RowPrototypeInterface::class);
        return static function (array $withData) use ($prototype): UserInterface {
            Type\non_empty_dict(
                Type\string(),
                Type\mixed(),
            )->assert($withData);
            return new $prototype(...$withData);
        };
    }
}
