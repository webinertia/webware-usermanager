<?php

declare(strict_types=1);

namespace WebwareTestIntegration\UserManager\Support;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Sql;
use Webware\UserManager\Console\Schema\UserSchema as ConsoleUserSchema;
use Webware\UserManager\Repository\Schema;

use function sprintf;

/**
 * Test-facing helper that executes the production user schema DDL against a
 * live adapter. Delegates to Webware\UserManager\Console\Schema\UserSchema so the
 * integration-test schema cannot drift from the real schema.
 */
final class UserSchema
{
    public static function create(AdapterInterface $adapter): void
    {
        $sql = new Sql($adapter);

        $adapter->query(
            $sql->buildSqlString(new ConsoleUserSchema()->userTable()),
            AdapterInterface::QUERY_MODE_EXECUTE,
        );
    }

    public static function drop(AdapterInterface $adapter): void
    {
        $sql = new Sql($adapter);

        foreach (new ConsoleUserSchema()->dropTables() as $drop) {
            $adapter->query(
                $sql->buildSqlString($drop),
                AdapterInterface::QUERY_MODE_EXECUTE,
            );
        }
    }

    public static function truncate(AdapterInterface $adapter): void
    {
        $adapter->executeQuery(sql: sprintf('DELETE FROM `%s`', Schema::User->value));
    }
}
