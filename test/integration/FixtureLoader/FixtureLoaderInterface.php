<?php

declare(strict_types=1);

namespace WebwareTestIntegration\UserManager\FixtureLoader;

/**
 * Loads and tears down the integration-test schema for one database platform.
 *
 * Unlike webware-core's database-level loaders (which create/drop the whole
 * database as root), usermanager's tests run as the unprivileged `webware`
 * user against a pre-provisioned database, so loaders operate at table level.
 */
interface FixtureLoaderInterface
{
    public function load(): void;

    public function unload(): void;
}
