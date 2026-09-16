<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Command;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Command\CreateUserCommand;

use function bin2hex;
use function password_verify;
use function random_bytes;

#[CoversClass(CreateUserCommand::class)]
#[CoversMethod(CreateUserCommand::class, '__construct')]
final class CreateUserCommandTest extends TestCase
{
    #[Test]
    public function constructorHashesPlaintextPassword(): void
    {
        $plaintext = bin2hex(random_bytes(16));

        $command = $this->command(passwordHash: $plaintext);

        static::assertNotSame($plaintext, $command->passwordHash);
        static::assertTrue(password_verify($plaintext, $command->passwordHash));
    }

    #[Test]
    public function constructorLowercasesEmail(): void
    {
        $command = $this->command(email: 'JANE@EXAMPLE.COM');

        static::assertSame('jane@example.com', $command->email);
    }

    #[Test]
    public function defaultsActiveToFalse(): void
    {
        static::assertFalse($this->command()->active);
    }

    #[Test]
    public function getCommandNameReturnsFullyQualifiedClassName(): void
    {
        static::assertSame(CreateUserCommand::class, $this->command()->getCommandName());
    }

    #[Test]
    public function roleIdAcceptsValidJsonString(): void
    {
        $command = $this->command(roleId: '["member"]');

        static::assertSame('["member"]', $command->roleId);
    }

    #[Test]
    public function roleIdArrayIsEncodedToJson(): void
    {
        $command = $this->command(roleId: ['member', 'admin']);

        static::assertSame('["member","admin"]', $command->roleId);
    }

    #[Test]
    public function roleIdRejectsInvalidString(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->command(roleId: 'not-json');
    }

    #[Test]
    public function timestampInputsAreFormattedToDatabaseFormat(): void
    {
        $command = $this->command(
            createdAt     : new DateTimeImmutable('2024-01-02 03:04:05'),
            tokenCreatedAt: new DateTimeImmutable('2024-01-02 03:04:05'),
        );

        static::assertSame('2024-01-02 03:04:05', $command->createdAt);
        static::assertSame('2024-01-02 03:04:05', $command->tokenCreatedAt);
    }

    /**
     * @param list<string>|string $roleId
     */
    private function command(
        string $email = 'jane@example.com',
        ?string $passwordHash = null,
        array|string $roleId = ['member'],
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $tokenCreatedAt = null,
    ): CreateUserCommand {
        $parameters = [
            'firstName'         => 'Jane',
            'lastName'          => 'Doe',
            'passwordHash'      => $passwordHash ?? bin2hex(random_bytes(16)),
            'email'             => $email,
            'roleId'            => $roleId,
            'verificationToken' => bin2hex(random_bytes(16)),
        ];

        if (null !== $createdAt) {
            $parameters['createdAt'] = $createdAt;
        }

        if (null !== $tokenCreatedAt) {
            $parameters['tokenCreatedAt'] = $tokenCreatedAt;
        }

        return new CreateUserCommand(...$parameters);
    }
}
