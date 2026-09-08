<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Core\SchemaInterface;
use Webware\UserManager\Repository\Schema;

use function is_string;

#[CoversClass(Schema::class)]
final class SchemaTest extends TestCase
{
    #[Test]
    public function schemaIsStringBacked(): void
    {
        static::assertTrue(is_string(Schema::User->value));
        static::assertInstanceOf(SchemaInterface::class, Schema::User);
    }

    #[Test]
    public function userCaseResolvesToUserTable(): void
    {
        static::assertSame('user', Schema::User->value);
    }
}
