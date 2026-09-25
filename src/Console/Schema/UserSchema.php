<?php

declare(strict_types=1);

namespace Webware\UserManager\Console\Schema;

use PhpDb\Sql\Argument\Literal as ArgLiteral;
use PhpDb\Sql\Ddl\Column\Datetime;
use PhpDb\Sql\Ddl\Column\Integer;
use PhpDb\Sql\Ddl\Column\Json;
use PhpDb\Sql\Ddl\Column\Varchar;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PhpDb\Sql\Ddl\Constraint\UniqueKey;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Literal;
use PhpDb\Sql\TableIdentifier;
use Webware\UserManager\Console\Ddl\Column\TinyInteger;
use Webware\UserManager\Repository\Schema;

/**
 * Builds the user database schema.
 *
 * The column set mirrors the user table migrated from the
 * inventory-management-system (see IMS Migration002User). There is no base
 * seed: users are provisioned at runtime, either through registration or the
 * interactive user created by {@see InitDbCommand}.
 */
final class UserSchema
{
    /**
     * @return list<DropTable>
     *
     * @throws InvalidArgumentException
     */
    public function dropTables(): array
    {
        return [
            new DropTable(table: new TableIdentifier(Schema::User->value))->ifExists(),
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    public function userTable(): CreateTable
    {
        $table = new CreateTable(table: new TableIdentifier(Schema::User->value))->ifNotExists();

        $table->addColumn(
            new Integer(
                name    : 'id',
                nullable: false,
            )->setOptions(options: ['unsigned' => true, 'autoincrement' => true]),
        );
        $table->addColumn(new Varchar(
            name    : 'roleId',
            length  : 50,
            nullable: false,
        ));
        $table->addColumn(new Varchar(
            name  : 'firstName',
            length: 75,
        ));
        $table->addColumn(new Varchar(
            name  : 'lastName',
            length: 75,
        ));
        $table->addColumn(new Varchar(
            name  : 'email',
            length: 255,
        ));
        $table->addColumn(
            new Varchar(
                name  : 'passwordHash',
                length: 255,
            )->setOptions(options: ['comment' => 'bcrypt hash; never store plain text']),
        );
        $table->addColumn(new TinyInteger(
            name    : 'active',
            nullable: false,
            default : 0,
        ));
        $table->addColumn(new Varchar(
            name    : 'verificationToken',
            length  : 36,
            nullable: true,
        ));
        $table->addColumn(new Datetime(
            name    : 'tokenCreatedAt',
            nullable: true,
        ));
        $table->addColumn(
            new Datetime(
                name    : 'createdAt',
                nullable: false,
                default : new ArgLiteral(literal: 'CURRENT_TIMESTAMP'),
            ),
        );
        $table->addColumn(
            new Json(
                name    : 'details',
                nullable: true,
            )->setOptions(options: ['comment' => 'Plugin extension data - storeId, etc. as JSON']),
        );

        $table->addConstraint(new PrimaryKey(columns: 'id'));
        $table->addConstraint(new UniqueKey(
            columns: 'email',
            name   : 'uq_user_email',
        ));

        $table->setOptions(options: [
            'engine'          => new Literal(literal: 'InnoDB'),
            'default charset' => new Literal(literal: 'utf8mb4'),
            'collate'         => new Literal(literal: 'utf8mb4_0900_ai_ci'),
        ]);

        return $table;
    }
}
