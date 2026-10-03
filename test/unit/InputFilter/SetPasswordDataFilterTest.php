<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\InputFilter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SensitiveParameter;
use Webware\UserManager\InputFilter\SetPasswordDataFilter;
use WebwareTest\UserManager\Support\InputFilterHelper;

use function bin2hex;
use function random_bytes;
use function random_int;

#[CoversClass(SetPasswordDataFilter::class)]
#[CoversMethod(SetPasswordDataFilter::class, 'init')]
final class SetPasswordDataFilterTest extends TestCase
{
    /** @return array<string, array{password: mixed}> */
    public static function rejectedPasswordProvider(): array
    {
        return [
            'too short'  => ['password' => bin2hex(random_bytes(2))],
            'an array'   => ['password' => [bin2hex(random_bytes(16))]],
            'an integer' => ['password' => random_int(
                min: 100_000,
                max: 999_999,
            )],
        ];
    }

    /** @return array<string, array{field: string}> */
    public static function requiredFieldProvider(): array
    {
        return [
            'passwordHash'        => ['field' => 'passwordHash'],
            'confirmPasswordHash' => ['field' => 'confirmPasswordHash'],
        ];
    }

    #[Test]
    public function acceptsMatchingPasswordsOfSufficientLength(): void
    {
        $password = bin2hex(random_bytes(16));

        $result = InputFilterHelper::setPasswordDataFilter()->validate([
            'passwordHash'        => $password,
            'confirmPasswordHash' => $password,
        ]);

        static::assertTrue($result->valid());
        static::assertSame($password, $result->value()['passwordHash']);
    }

    #[Test]
    #[DataProvider('rejectedPasswordProvider')]
    public function rejectsAnUnusablePassword(#[SensitiveParameter] mixed $password): void
    {
        $result = InputFilterHelper::setPasswordDataFilter()->validate([
            'passwordHash'        => $password,
            'confirmPasswordHash' => $password,
        ]);

        static::assertFalse($result->valid());
    }

    #[Test]
    public function rejectsMismatchedPasswords(): void
    {
        $result = InputFilterHelper::setPasswordDataFilter()->validate([
            'passwordHash'        => bin2hex(random_bytes(16)),
            'confirmPasswordHash' => bin2hex(random_bytes(16)),
        ]);

        static::assertFalse($result->valid());
        static::assertSame(
            ['notSame' => 'Passwords do not match.'],
            $result->getMessages()->toArray()['confirmPasswordHash'],
        );
    }

    #[Test]
    #[DataProvider('requiredFieldProvider')]
    public function requiresEveryField(string $field): void
    {
        $data = [
            'passwordHash'        => bin2hex(random_bytes(16)),
            'confirmPasswordHash' => bin2hex(random_bytes(16)),
        ];

        // Matching values keep the mismatch validator from masking the missing field.
        $data['confirmPasswordHash'] = $data['passwordHash'];

        unset($data[$field]);

        static::assertFalse(InputFilterHelper::setPasswordDataFilter()->validate($data)->valid());
    }
}
