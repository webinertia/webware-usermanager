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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Webware\Core\Role;
use Webware\UserManager\Console\Schema\UserSchema;
use Webware\UserManager\Repository\Schema;

use function implode;
use function in_array;
use function password_hash;
use function sprintf;
use function str_replace;
use function strtolower;
use function ucfirst;

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
     * The mandatory values, in the order they are asked for when missing.
     *
     * A user must be created, so these are declared as required arguments rather
     * than options: Symfony has no way to mark an option required, and a value
     * the operator has to give is invisible to anything reading the definition.
     *
     * @var list<string>
     */
    private const array REQUIRED_ARGUMENTS = ['first-name', 'last-name', 'email', 'password'];

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
        $this->addArgument(
            name       : 'first-name',
            mode       : InputArgument::REQUIRED,
            description: 'First name for the seeded user',
        );
        $this->addArgument(
            name       : 'last-name',
            mode       : InputArgument::REQUIRED,
            description: 'Last name for the seeded user',
        );
        $this->addArgument(
            name       : 'email',
            mode       : InputArgument::REQUIRED,
            description: 'Email for the seeded user',
        );
        $this->addArgument(
            name       : 'password',
            mode       : InputArgument::REQUIRED,
            description: 'Password for the seeded user',
        );
        $this->addOption(
            name       : 'role',
            mode       : InputOption::VALUE_REQUIRED,
            description: 'Role for the seeded user',
            default    : Role::Developer->value,
        );
        $this->addOption(
            name       : 'drop',
            mode       : InputOption::VALUE_NONE,
            description: 'Drop the user table before recreating it',
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
        $row = $this->collectUser($input);
        $this->executeInsert($sql, Schema::User->value, $row);

        $output->writeln(sprintf('User created: %s — roles: %s', $row['email'], $row['roleId']));
        $output->writeln('User database initialized.');

        return Command::SUCCESS;
    }

    /**
     * Asks for any mandatory value that was not supplied.
     *
     * Symfony runs this before the definition is validated, which is the only
     * point at which a required argument can still be filled in interactively —
     * so the command stays usable when run directly with no arguments.
     *
     * @throws ConsoleInvalidArgumentException
     * @throws ConsoleLogicException
     * @throws ConsoleRuntimeException
     */
    #[Override]
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $helper = new QuestionHelper();

        foreach (self::REQUIRED_ARGUMENTS as $name) {
            if (null !== $input->getArgument($name)) {
                continue;
            }

            $input->setArgument(
                name: $name,
                value: $helper->ask($input, $output, $this->questionFor($name)),
            );
        }
    }

    /**
     * @throws ConsoleInvalidArgumentException
     * @throws JsonException
     *
     * @return array{roleId: string, firstName: string, lastName: string, email: string, passwordHash: string, active: int}
     */
    private function collectUser(InputInterface $input): array
    {
        return [
            'roleId'       => $this->resolveRole($input),
            'firstName'    => (string) $input->getArgument('first-name'),
            'lastName'     => (string) $input->getArgument('last-name'),
            'email'        => strtolower((string) $input->getArgument('email')),
            'passwordHash' => password_hash((string) $input->getArgument('password'), PASSWORD_DEFAULT),
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

    /**
     * @throws ConsoleLogicException
     */
    private function questionFor(string $name): Question
    {
        $prompt =
            ucfirst(str_replace(
                search : '-',
                replace: ' ',
                subject: $name,
            )) . ': ';

        return match ($name) {
            'password' => new Question('Password: ')->setHidden(true)
                ->setHiddenFallback(false),
            default    => new Question($prompt),
        };
    }

    /**
     * @throws ConsoleInvalidArgumentException
     */
    private function resolveRole(InputInterface $input): string
    {
        $role = (string) $input->getOption('role');

        if (! in_array(
            needle  : $role,
            haystack: self::ROLES,
            strict  : true,
        )) {
            throw new ConsoleInvalidArgumentException(
                sprintf('Invalid role "%s"; expected one of: %s.', $role, implode(', ', self::ROLES)),
            );
        }

        return $role;
    }
}
