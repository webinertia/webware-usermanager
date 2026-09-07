<?php

declare(strict_types=1);

namespace Webware\UserManager\Repository;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\ResultSet\RowPrototypeInterface;
use PhpDb\ResultSet\RowPrototypeResultSet;
use PhpDb\Sql\Exception\ExceptionInterface as SqlException;
use PhpDb\TableGateway\Exception\ExceptionInterface as TableGatewayException;
use PhpDb\TableGateway\TableGateway;
use Psl\Type\Exception\ExceptionInterface as PslTypeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\Core\SchemaFactory;
use Webware\UserManager\Container\Configuration;

final class UserRepositoryFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws SqlException
     * @throws TableGatewayException
     * @throws PslTypeException
     */
    public function __invoke(ContainerInterface $container): UserRepository
    {
        $config = Configuration::getCredentialConfig($container, self::class);
        /** @var string $username */
        $username = $config['username'];
        return new UserRepository(
            gateway         : new TableGateway(
                table             : $container->get(SchemaFactory::class)(Schema::User),
                adapter           : $container->get(AdapterInterface::class),
                resultSetPrototype: new RowPrototypeResultSet(
                    rowPrototype: $container->get(RowPrototypeInterface::class),
                ),
            ),
            dispatcher      : $container->get(EventDispatcherInterface::class),
            credentialColumn: $username,
        );
    }
}
