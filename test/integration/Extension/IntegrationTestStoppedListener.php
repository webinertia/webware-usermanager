<?php

declare(strict_types=1);

namespace WebwareTestIntegration\UserManager\Extension;

use PHPUnit\Event\TestSuite\Finished;
use PHPUnit\Event\TestSuite\FinishedSubscriber;
use WebwareTestIntegration\UserManager\FixtureLoader\FixtureLoaderInterface;

final readonly class IntegrationTestStoppedListener implements FinishedSubscriber
{
    /**
     * @param list<FixtureLoaderInterface> $fixtureLoaders
     */
    public function __construct(
        private array $fixtureLoaders,
    ) {}

    public function notify(Finished $event): void
    {
        if ('integration test' !== $event->testSuite()->name() || [] === $this->fixtureLoaders) {
            return;
        }

        foreach ($this->fixtureLoaders as $fixtureLoader) {
            $fixtureLoader->unload();
        }
    }
}
