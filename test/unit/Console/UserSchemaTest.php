<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Console;

use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Mysql\AdapterPlatform;
use PhpDb\Mysql\Sql\Platform;
use PhpDb\Sql\Ddl\Column\ColumnInterface;
use PhpDb\Sql\Ddl\Column\Datetime;
use PhpDb\Sql\Ddl\Column\Integer;
use PhpDb\Sql\Ddl\Column\Json;
use PhpDb\Sql\Ddl\Column\Varchar;
use PhpDb\Sql\Ddl\Constraint\ConstraintInterface;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PhpDb\Sql\Ddl\Constraint\UniqueKey;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\Literal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Console\Ddl\Column\TinyInteger;
use Webware\UserManager\Console\UserSchema;

use function array_keys;

#[CoversClass(UserSchema::class)]
final class UserSchemaTest extends TestCase
{
    #[Test]
    public function dropTablesDropsUserTable(): void
    {
        $tables = new UserSchema()->dropTables();

        self::assertCount(1, $tables);
        self::assertContainsOnlyInstancesOf(DropTable::class, $tables);
        self::assertTrue($tables[0]->getIfExists());
        self::assertStringContainsString('user', $tables[0]->getSqlString());
    }

    #[Test]
    public function userTableBuildsUserSchema(): void
    {
        $table = new UserSchema()->userTable();

        self::assertTrue($table->getIfNotExists());

        $columns = $this->columns($table);
        self::assertCount(11, $columns);

        self::assertInstanceOf(Integer::class, $columns[0]);
        self::assertSame('id', $columns[0]->getName());
        self::assertFalse($columns[0]->isNullable());
        self::assertSame(['unsigned' => true, 'autoincrement' => true], $columns[0]->getOptions());

        self::assertInstanceOf(Varchar::class, $columns[1]);
        self::assertSame('roleId', $columns[1]->getName());
        self::assertFalse($columns[1]->isNullable());

        self::assertInstanceOf(Varchar::class, $columns[2]);
        self::assertSame('firstName', $columns[2]->getName());
        self::assertSame(75, $columns[2]->getLength());
        self::assertFalse($columns[2]->isNullable());

        self::assertInstanceOf(Varchar::class, $columns[3]);
        self::assertSame('lastName', $columns[3]->getName());
        self::assertSame(75, $columns[3]->getLength());
        self::assertFalse($columns[3]->isNullable());

        self::assertInstanceOf(Varchar::class, $columns[4]);
        self::assertSame('email', $columns[4]->getName());
        self::assertSame(255, $columns[4]->getLength());
        self::assertFalse($columns[4]->isNullable());

        self::assertInstanceOf(Varchar::class, $columns[5]);
        self::assertSame('passwordHash', $columns[5]->getName());
        self::assertSame(255, $columns[5]->getLength());
        self::assertSame(['comment' => 'bcrypt hash; never store plain text'], $columns[5]->getOptions());

        self::assertInstanceOf(TinyInteger::class, $columns[6]);
        self::assertSame('active', $columns[6]->getName());
        self::assertFalse($columns[6]->isNullable());
        self::assertSame(0, $columns[6]->getDefault());

        self::assertInstanceOf(Varchar::class, $columns[7]);
        self::assertSame('verificationToken', $columns[7]->getName());
        self::assertSame(36, $columns[7]->getLength());
        self::assertTrue($columns[7]->isNullable());

        self::assertInstanceOf(Datetime::class, $columns[8]);
        self::assertSame('tokenCreatedAt', $columns[8]->getName());
        self::assertTrue($columns[8]->isNullable());

        self::assertInstanceOf(Datetime::class, $columns[9]);
        self::assertSame('createdAt', $columns[9]->getName());
        self::assertFalse($columns[9]->isNullable());

        self::assertInstanceOf(Json::class, $columns[10]);
        self::assertSame('details', $columns[10]->getName());
        self::assertTrue($columns[10]->isNullable());
        self::assertSame(['comment' => 'Plugin extension data - storeId, etc. as JSON'], $columns[10]->getOptions());

        $constraints = $this->constraints($table);
        self::assertCount(2, $constraints);
        self::assertInstanceOf(PrimaryKey::class, $constraints[0]);
        self::assertSame(['id'], $constraints[0]->getColumns());
        self::assertInstanceOf(UniqueKey::class, $constraints[1]);
        self::assertSame(['email'], $constraints[1]->getColumns());
        self::assertSame('uq_user_email', $constraints[1]->getExpressionData()['values'][0]->getValue());

        $this->assertTableOptions($table);
    }

    #[Test]
    public function userTableRendersValidMysqlDdl(): void
    {
        $sql = $this->renderSql(new UserSchema()->userTable());

        self::assertStringContainsString('CREATE TABLE', $sql);
        self::assertStringContainsString('IF NOT EXISTS', $sql);
        self::assertStringContainsString('`user`', $sql);
        self::assertStringContainsString('UNSIGNED', $sql);
        self::assertStringContainsString('AUTO_INCREMENT', $sql);
        self::assertStringContainsString('VARCHAR(75)', $sql);
        self::assertStringContainsString('VARCHAR(255)', $sql);
        self::assertStringContainsString('JSON', $sql);
        self::assertStringContainsString('TINYINT', $sql);
        self::assertStringContainsString('DATETIME', $sql);
        self::assertStringContainsString('PRIMARY KEY', $sql);
        self::assertStringContainsString('uq_user_email', $sql);
        self::assertStringContainsString('ENGINE = InnoDB', $sql);
        self::assertStringContainsString('utf8mb4_0900_ai_ci', $sql);
    }

    private function assertTableOptions(CreateTable $table): void
    {
        $options = $table->getOptions();

        self::assertSame(['engine', 'default charset', 'collate'], array_keys($options));
        self::assertInstanceOf(Literal::class, $options['engine']);
        self::assertSame('InnoDB', $options['engine']->getLiteral());
        self::assertSame('utf8mb4', $options['default charset']->getLiteral());
        self::assertSame('utf8mb4_0900_ai_ci', $options['collate']->getLiteral());
    }

    /**
     * @return list<ColumnInterface>
     */
    private function columns(CreateTable $table): array
    {
        /** @var list<ColumnInterface> */
        return $table->getRawState('columns');
    }

    /**
     * @return list<ConstraintInterface>
     */
    private function constraints(CreateTable $table): array
    {
        /** @var list<ConstraintInterface> */
        return $table->getRawState('constraints');
    }

    private function renderSql(CreateTable|DropTable $sql): string
    {
        $driver     = $this->createStub(DriverInterface::class);
        $connection = $this->createStub(ConnectionInterface::class);
        $driver->method('getConnection')->willReturn($connection);

        $platform = new Platform();
        $platform->setSubject($sql);

        return $platform->getSqlString(new AdapterPlatform($driver));
    }
}
