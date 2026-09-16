<?php

declare(strict_types=1);

namespace Webware\UserManager\Container;

use Psl\Type;
use Psl\Type\Exception\ExceptionInterface as PslTypeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Core\UserInterface;
use Webware\UserManager\Entity\User;

/**
 * DI factory for the UserInterface::class callable service.
 *
 * Returns a callable that creates a UserInterface implementation from an array of data.
 * The callable is used by IdentityMiddleware to reconstruct the authenticated user from session data.
 */
final class UserFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): callable
    {
        $prototype = $container->get(User::class);

        return (
            /**
             * @throws PslTypeException
             */
            static function (array $withData) use ($prototype): UserInterface {
                Type\non_empty_dict(
                    Type\string(),
                    Type\mixed(),
                )->assert($withData);
                return $prototype->populate($withData);
            }
        );
    }
}
