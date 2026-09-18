<?php

declare(strict_types=1);

namespace Webware\UserManager\Console;

use JsonException;
use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\Exception\InvalidArgumentException as SqlInvalidArgumentException;
use PhpDb\Sql\InsertIgnore;
use PhpDb\Sql\Sql;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException as ConsoleInvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException as ConsoleLogicException;
use Symfony\Component\Console\Exception\RuntimeException as ConsoleRuntimeException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Webware\Core\Role;
use Webware\UserManager\Repository\Schema;

use function password_hash;
use function sprintf;
use function strtolower;

use const PASSWORD_DEFAULT;

#[AsCommand(
    name       : 'user:init-db',
    description: 'Create the user database schema and seed an initial user',
)]
final class InitDbCommand extends Command
{
    /**
     * @var list<string>
     */
    private const array ROLES = [
        Role::Developer->value,
        Role::Administrator->value,
        Role::Member->value,
        Role::Guest->value,
    ];

    /**
     * @throws ConsoleLogicException
     */
    public function __construct(
        private readonly AdapterInterface $adapter,
    ) {
        parent::__construct();
    }

    /**
     * @throws ConsoleInvalidArgumentException
     */
    #[Override]
    protected function configure(): void
    {
        $this->addOption(
            name       : 'drop',
            mode       : InputOption::VALUE_NONE,
            description: 'Drop the user table before recreating it',
        );
        $this->addOption(
            name       : 'first-name',
            mode       : InputOption::VALUE_REQUIRED,
            description: 'First name for the seeded user',
        );
        $this->addOption(
            name       : 'last-name',
            mode       : InputOption::VALUE_REQUIRED,
            description: 'Last name for the seeded user',
        );
        $this->addOption(
            name       : 'email',
            mode       : InputOption::VALUE_REQUIRED,
            description: 'Email for the seeded user',
        );
        $this->addOption(
            name       : 'password',
            mode       : InputOption::VALUE_REQUIRED,
            description: 'Password for the seeded user',
        );
        $this->addOption(
            name       : 'role',
            mode       : InputOption::VALUE_REQUIRED,
            description: 'Role for the seeded user',
            default    : Role::Developer->value,
        );
    }

    /**
     * @throws ConsoleInvalidArgumentException
     * @throws ConsoleLogicException
     * @throws ConsoleRuntimeException
     * @throws JsonException
     * @throws SqlInvalidArgumentException
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sql    = new Sql($this->adapter);
        $schema = new UserSchema();

        if (true === $input->getOption('drop')) {
            $output->writeln('Dropping existing user table...');
            foreach ($schema->dropTables() as $table) {
                $this->executeDdl($sql, $table);
            }
        }

        $output->writeln('Creating user schema...');
        $this->executeDdl($sql, $schema->userTable());

        $output->writeln('Creating initial user...');
        $row = $this->collectUser($input, $output);
        $this->executeInsert($sql, Schema::User->value, $row);

        $output->writeln(sprintf('User created: %s — roles: %s', $row['email'], $row['roleId']));
        $output->writeln('User database initialized.');

        return Command::SUCCESS;
    }

    /**
     * @throws ConsoleInvalidArgumentException
     * @throws ConsoleLogicException
     * @throws ConsoleRuntimeException
     * @throws JsonException
     *
     * @return array{roleId: string, firstName: string, lastName: string, email: string, passwordHash: string, active: int}
     */
    private function collectUser(InputInterface $input, OutputInterface $output): array
    {
        $helper = new QuestionHelper();

        $firstName = (string) (
            $input->getOption('first-name') ?? $helper->ask($input, $output, new Question('First name: '))
        );
        $lastName = (string) (
            $input->getOption('last-name') ?? $helper->ask($input, $output, new Question('Last name: '))
        );
        $email    = (string) ($input->getOption('email') ?? $helper->ask($input, $output, new Question('Email: ')));
        $password = (string) (
            $input->getOption('password') ?? $helper->ask(
                $input,
                $output,
                new Question('Password: ')->setHidden(true)
                    ->setHiddenFallback(false),
            )
        );
        $role = (string) (
            $input->getOption('role') ?? $helper->ask(
                $input,
                $output,
                new ChoiceQuestion('Role:', self::ROLES, Role::Developer->value),
            )
        );

        return [
            'roleId'       => $role,
            'firstName'    => $firstName,
            'lastName'     => $lastName,
            'email'        => strtolower($email),
            'passwordHash' => password_hash($password, PASSWORD_DEFAULT),
            'active'       => 1,
        ];
    }

    /**
     * @throws SqlInvalidArgumentException
     */
    private function executeDdl(Sql $sql, CreateTable|DropTable $ddl): void
    {
        $this->adapter->query(
            $sql->buildSqlString($ddl),
            AdapterInterface::QUERY_MODE_EXECUTE,
        );
    }

    /**
     * @param array<string, mixed> $row
     *
     * @throws SqlInvalidArgumentException
     */
    private function executeInsert(Sql $sql, string $table, array $row): void
    {
        $sql->prepareStatementForSqlObject(
            new InsertIgnore(table: $table)->values($row),
        )->execute();
    }
}
