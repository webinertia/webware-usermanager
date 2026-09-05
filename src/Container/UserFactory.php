<?php

declare(strict_types=1);

namespace Webware\UserManager\Container;

use PhpDb\ResultSet\RowPrototypeInterface;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;
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
        $config    = Configuration::getCredentialConfig($container, self::class);
        return static function (array $withData) use ($prototype, $config): UserInterface {
            Assert::isMap($withData);
            return new $prototype(...$withData);
        };
    }
}
