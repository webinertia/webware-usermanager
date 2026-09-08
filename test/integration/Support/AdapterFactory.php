<?php

declare(strict_types=1);

namespace WebwareTestIntegration\UserManager\Support;

use Laminas\ServiceManager\ServiceManager;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\ConfigProvider as PhpDbConfigProvider;
use PhpDb\Mysql;

use function getenv;

/**
 * Builds a phpdb adapter from connection config plus a single driver's
 * dependencies. Each driver gets its own isolated ServiceManager, since phpdb
 * supports only one driver package per container.
 */
final class AdapterFactory
{
    /**
     * @param array<string, mixed> $adapterConfig
     * @param array<string, mixed> $driverDependencies
     */
    public static function create(array $adapterConfig, array $driverDependencies): AdapterInterface
    {
        $container = new ServiceManager();
        $container->configure(new PhpDbConfigProvider()->getDependencies());
        $container->configure($driverDependencies);
        $container->setService('config', [AdapterInterface::class => $adapterConfig]);

        return $container->get(AdapterInterface::class);
    }

    public static function mysql(): AdapterInterface
    {
        return self::create(
            adapterConfig     : [
                'driver'     => Mysql\Pdo\Driver::class,
                'connection' => [
                    'hostname' => (string) getenv(name: 'TESTS_ADAPTER_MYSQL_HOSTNAME'),
                    'port'     => (string) getenv(name: 'TESTS_ADAPTER_MYSQL_PORT'),
                    'username' => (string) getenv(name: 'TESTS_ADAPTER_MYSQL_USERNAME'),
                    'password' => (string) getenv(name: 'TESTS_ADAPTER_MYSQL_PASSWORD'),
                    'database' => (string) getenv(name: 'TESTS_ADAPTER_MYSQL_DATABASE'),
                ],
            ],
            driverDependencies: new Mysql\ConfigProvider()->getDependencies(),
        );
    }
}
