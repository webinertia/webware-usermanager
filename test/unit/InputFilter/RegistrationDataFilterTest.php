<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\InputFilter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Webware\UserManager\InputFilter\RegistrationDataFilter;
use WebwareTest\UserManager\Support\InputFilterHelper;

use function bin2hex;
use function random_bytes;

#[CoversClass(RegistrationDataFilter::class)]
#[CoversMethod(RegistrationDataFilter::class, 'init')]
final class RegistrationDataFilterTest extends TestCase
{
    #[Test]
    public function acceptsEmptyVerificationToken(): void
    {
        $data                      = $this->validData();
        $data['verificationToken'] = '';

        $result = $this->filter()->validate($data);

        self::assertTrue($result->valid());
    }

    #[Test]
    public function acceptsValidRegistrationData(): void
    {
        $password = bin2hex(random_bytes(16));

        $result = $this->filter()->validate([
            'firstName'           => ' Jane ',
            'lastName'            => ' Doe ',
            'email'               => ' jane@example.com ',
            'passwordHash'        => $password,
            'confirmPasswordHash' => $password,
            'verificationToken'   => Uuid::uuid4()->toString(),
            'roleId'              => ' ["member"] ',
        ]);

        self::assertTrue($result->valid());
        self::assertSame('Jane', $result->value()['firstName']);
        self::assertSame('Doe', $result->value()['lastName']);
        self::assertSame('jane@example.com', $result->value()['email']);
        self::assertSame('["member"]', $result->value()['roleId']);
        self::assertFalse($result->value()['active']);
    }

    #[Test]
    public function castsActiveToBoolean(): void
    {
        $data           = $this->validData();
        $data['active'] = '1';

        $result = $this->filter()->validate($data);

        self::assertTrue($result->value()['active']);
    }

    #[Test]
    public function rejectsInvalidEmail(): void
    {
        $password = bin2hex(random_bytes(16));

        $result = $this->filter()->validate([
            'firstName'           => 'Jane',
            'lastName'            => 'Doe',
            'email'               => 'not-an-email',
            'passwordHash'        => $password,
            'confirmPasswordHash' => $password,
            'roleId'              => '["member"]',
        ]);

        self::assertFalse($result->valid());
        self::assertArrayHasKey('email', $result->getMessages());
    }

    #[Test]
    public function rejectsInvalidVerificationToken(): void
    {
        $data                      = $this->validData();
        $data['verificationToken'] = bin2hex(random_bytes(16));

        $result = $this->filter()->validate($data);

        self::assertFalse($result->valid());
        self::assertArrayHasKey('verificationToken', $result->getMessages());
    }

    #[Test]
    public function rejectsMismatchedPasswords(): void
    {
        $result = $this->filter()->validate([
            'firstName'           => 'Jane',
            'lastName'            => 'Doe',
            'email'               => 'jane@example.com',
            'passwordHash'        => bin2hex(random_bytes(16)),
            'confirmPasswordHash' => bin2hex(random_bytes(16)),
            'roleId'              => '["member"]',
        ]);

        self::assertFalse($result->valid());
        self::assertArrayHasKey('confirmPasswordHash', $result->getMessages());
    }

    #[Test]
    public function rejectsMissingConfirmPasswordHash(): void
    {
        $data = $this->validData();
        unset($data['confirmPasswordHash']);

        $result = $this->filter()->validate($data);

        self::assertFalse($result->valid());
        self::assertArrayHasKey('confirmPasswordHash', $result->getMessages());
    }

    #[Test]
    public function rejectsMissingEmail(): void
    {
        $data = $this->validData();
        unset($data['email']);

        $result = $this->filter()->validate($data);

        self::assertFalse($result->valid());
        self::assertArrayHasKey('email', $result->getMessages());
    }

    #[Test]
    public function rejectsMissingFirstName(): void
    {
        $data = $this->validData();
        unset($data['firstName']);

        $result = $this->filter()->validate($data);

        self::assertFalse($result->valid());
        self::assertArrayHasKey('firstName', $result->getMessages());
    }

    #[Test]
    public function rejectsMissingLastName(): void
    {
        $data = $this->validData();
        unset($data['lastName']);

        $result = $this->filter()->validate($data);

        self::assertFalse($result->valid());
        self::assertArrayHasKey('lastName', $result->getMessages());
    }

    #[Test]
    public function rejectsMissingPasswordHash(): void
    {
        $data = $this->validData();
        unset($data['passwordHash']);

        $result = $this->filter()->validate($data);

        self::assertFalse($result->valid());
        $messages = $result->getMessages()->toArray();
        self::assertArrayHasKey('passwordHash', $messages);
        self::assertNotSame([], $messages['passwordHash']);
    }

    #[Test]
    public function rejectsMissingRequiredFields(): void
    {
        $result = $this->filter()->validate([
            'email' => 'jane@example.com',
        ]);

        self::assertFalse($result->valid());
        self::assertArrayHasKey('firstName', $result->getMessages());
    }

    #[Test]
    public function rejectsMissingRoleId(): void
    {
        $data = $this->validData();
        unset($data['roleId']);

        $result = $this->filter()->validate($data);

        self::assertFalse($result->valid());
        self::assertArrayHasKey('roleId', $result->getMessages());
    }

    #[Test]
    public function reportsPasswordMismatchMessage(): void
    {
        $data                        = $this->validData();
        $data['confirmPasswordHash'] = bin2hex(random_bytes(16));

        $result = $this->filter()->validate($data);

        self::assertFalse($result->valid());
        self::assertSame(
            'Passwords do not match.',
            $result->getMessages()->toArray()['confirmPasswordHash']['notSame'],
        );
    }

    private function filter(): RegistrationDataFilter
    {
        return InputFilterHelper::registrationDataFilter();
    }

    /** @return array<string, mixed> */
    private function validData(): array
    {
        $password = bin2hex(random_bytes(16));

        return [
            'firstName'           => 'Jane',
            'lastName'            => 'Doe',
            'email'               => 'jane@example.com',
            'passwordHash'        => $password,
            'confirmPasswordHash' => $password,
            'verificationToken'   => Uuid::uuid4()->toString(),
            'roleId'              => '["member"]',
        ];
    }
}
