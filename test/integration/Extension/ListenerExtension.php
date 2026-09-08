<?php

declare(strict_types=1);

namespace WebwareTestIntegration\UserManager\Extension;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use WebwareTestIntegration\UserManager\FixtureLoader\FixtureLoaderInterface;
use WebwareTestIntegration\UserManager\FixtureLoader\MysqlFixtureLoader;

use function getenv;

final class ListenerExtension implements Extension
{
    public function bootstrap(
        Configuration $configuration,
        Facade $facade,
        ParameterCollection $parameters,
    ): void {
        $fixtureLoaders = $this->fixtureLoaders();

        $facade->registerSubscribers(
            new IntegrationTestStartedListener($fixtureLoaders),
            new IntegrationTestStoppedListener($fixtureLoaders),
        );
    }

    /**
     * @return list<FixtureLoaderInterface>
     */
    private function fixtureLoaders(): array
    {
        $loaders = [];

        if (getenv(name: 'TESTS_ADAPTER_MYSQL')) {
            $loaders[] = new MysqlFixtureLoader();
        }

        return $loaders;
    }
}
