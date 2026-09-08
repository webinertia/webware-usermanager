<?php

declare(strict_types=1);

namespace WebwareTestIntegration\UserManager\FixtureLoader;

use WebwareTestIntegration\UserManager\Support\AdapterFactory;
use WebwareTestIntegration\UserManager\Support\UserSchema;

final class MysqlFixtureLoader implements FixtureLoaderInterface
{
    public function load(): void
    {
        UserSchema::create(AdapterFactory::mysql());
    }

    public function unload(): void
    {
        UserSchema::drop(AdapterFactory::mysql());
    }
}
