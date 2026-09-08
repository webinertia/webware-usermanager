<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Console;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\InsertIgnore;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\SqlInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Webware\UserManager\Console\InitDbCommand;

use function array_combine;
use function bin2hex;
use function random_bytes;

#[CoversClass(InitDbCommand::class)]
final class InitDbCommandTest extends TestCase
{
    #[Test]
    public function configureDefinesCommandNameDescriptionAndOptions(): void
    {
        $command = new InitDbCommand($this->createStub(AdapterInterface::class));

        $definition = $command->getDefinition();

        self::assertSame('user:init-db', $command->getName());
        self::assertSame('Create the user database schema and seed an initial user', $command->getDescription());
        self::assertTrue($definition->hasOption('drop'));
        self::assertFalse($definition->getOption('drop')->acceptValue());
        self::assertTrue($definition->hasOption('first-name'));
        self::assertTrue($definition->hasOption('last-name'));
        self::assertTrue($definition->hasOption('email'));
        self::assertTrue($definition->hasOption('password'));
        self::assertTrue($definition->hasOption('role'));
        self::assertSame('Developer', $definition->getOption('role')->getDefault());
    }

    #[Test]
    public function executeCreatesSchemaAndSeedsUserWithoutDropping(): void
    {
        $command = new InitDbCommand($this->createAdapter(
            queryCalls         : 1,
            statementExecutions: 1,
        ));

        $tester = new CommandTester($command);
        $tester->execute([
            '--first-name' => 'Joey',
            '--last-name'  => 'Smith',
            '--email'      => 'jsmith@example.com',
            '--password'   => bin2hex(random_bytes(16)),
        ]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Creating user schema', $tester->getDisplay());
        self::assertStringContainsString('Creating initial user', $tester->getDisplay());
        self::assertStringContainsString('User created: jsmith@example.com', $tester->getDisplay());
        self::assertStringContainsString('User database initialized', $tester->getDisplay());
        self::assertStringNotContainsString('Dropping existing user table', $tester->getDisplay());
    }

    #[Test]
    public function executeDropsTableWhenRequested(): void
    {
        $command = new InitDbCommand($this->createAdapter(
            queryCalls         : 2,
            statementExecutions: 1,
        ));

        $tester = new CommandTester($command);
        $tester->execute([
            '--drop'       => true,
            '--first-name' => 'Joey',
            '--last-name'  => 'Smith',
            '--email'      => 'jsmith@example.com',
            '--password'   => bin2hex(random_bytes(16)),
        ]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Dropping existing user table', $tester->getDisplay());
        self::assertStringContainsString('Creating user schema', $tester->getDisplay());
        self::assertStringContainsString('User database initialized', $tester->getDisplay());
    }

    #[Test]
    public function executeNormalizesSeededUserData(): void
    {
        $capturedInsert = null;
        $command        = new InitDbCommand($this->createAdapter(
            queryCalls         : 1,
            statementExecutions: 1,
            capturedInsert     : $capturedInsert,
        ));

        $tester = new CommandTester($command);
        $tester->execute([
            '--first-name' => 'Joey',
            '--last-name'  => 'Smith',
            '--email'      => 'JSMITH@EXAMPLE.COM',
            '--password'   => bin2hex(random_bytes(16)),
        ]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertNotNull($capturedInsert);

        $row = $this->insertRow($capturedInsert);
        self::assertSame('jsmith@example.com', $row['email']);
        self::assertSame('["Developer"]', $row['roleId']);
        self::assertSame(1, $row['active']);
    }

    #[Test]
    public function roleOptionDefaultTakesPrecedenceOverPrompt(): void
    {
        $capturedInsert = null;
        $command        = new InitDbCommand($this->createAdapter(
            queryCalls         : 1,
            statementExecutions: 1,
            capturedInsert     : $capturedInsert,
        ));

        $tester = new CommandTester($command);
        $tester->setInputs(['Member']);
        $tester->execute([
            '--first-name' => 'Joey',
            '--last-name'  => 'Smith',
            '--email'      => 'jsmith@example.com',
            '--password'   => bin2hex(random_bytes(16)),
        ], ['interactive' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertNotNull($capturedInsert);

        self::assertSame('["Developer"]', $this->insertRow($capturedInsert)['roleId']);
    }

    private function createAdapter(
        int $queryCalls,
        int $statementExecutions,
        ?InsertIgnore &$capturedInsert = null,
    ): AdapterInterface {
        $platform  = $this->createStub(PlatformInterface::class);
        $driver    = $this->createStub(DriverInterface::class);
        $decorator = $this->createStubForIntersectionOfInterfaces([
            PlatformDecoratorInterface::class,
            SqlInterface::class,
            PreparableSqlInterface::class,
        ]);

        $decorator->method('setSubject')
            ->willReturnCallback(
                static function (SqlInterface|PreparableSqlInterface|null $subject) use (
                    $decorator,
                    &$capturedInsert,
                ): PlatformDecoratorInterface {
                    if ($subject instanceof InsertIgnore) {
                        $capturedInsert = $subject;
                    }

                    return $decorator;
                },
            );
        $decorator->method('getSqlString')->willReturn('');
        $decorator->method('prepareStatement')->willReturnArgument(1);

        $statement = $this->createMock(StatementInterface::class);
        $statement->expects($this->exactly($statementExecutions))->method('execute');

        $driver->method('createStatement')->willReturn($statement);
        $platform->method('getSqlPlatformDecorator')->willReturn($decorator);

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->method('getDriver')->willReturn($driver);
        $adapter->method('getPlatform')->willReturn($platform);
        $adapter->expects($this->exactly($queryCalls))
            ->method('query')
            ->with($this->anything(), AdapterInterface::QUERY_MODE_EXECUTE)
            ->willReturn($this->createStub(ResultInterface::class));

        return $adapter;
    }

    /**
     * @return array<string, mixed>
     */
    private function insertRow(InsertIgnore $insert): array
    {
        $columns = $insert->getRawState('columns');
        $values  = $insert->getRawState('values');

        self::assertIsArray($columns);
        self::assertIsArray($values);

        return array_combine($columns, $values);
    }
}
