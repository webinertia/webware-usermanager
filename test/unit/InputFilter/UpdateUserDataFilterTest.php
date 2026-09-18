<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\InputFilter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\InputFilter\UpdateUserDataFilter;
use WebwareTest\UserManager\Support\InputFilterHelper;

#[CoversClass(UpdateUserDataFilter::class)]
#[CoversMethod(UpdateUserDataFilter::class, 'init')]
final class UpdateUserDataFilterTest extends TestCase
{
    #[Test]
    public function acceptsEmptyIdAsNull(): void
    {
        $data       = $this->validData();
        $data['id'] = '';

        $result = $this->filter()->validate($data);

        self::assertTrue($result->valid());
        self::assertNull($result->value()['id']);
    }

    #[Test]
    public function acceptsValidUpdateData(): void
    {
        $result = $this->filter()->validate([
            'id'        => '42',
            'firstName' => ' Jane ',
            'lastName'  => ' Doe ',
            'email'     => ' jane@example.com ',
            'roleId'    => 'Member',
            'active'    => '1',
        ]);

        self::assertTrue($result->valid());
        self::assertSame(42, $result->value()['id']);
        self::assertSame('Jane', $result->value()['firstName']);
        self::assertSame('jane@example.com', $result->value()['email']);
        self::assertSame('Member', $result->value()['roleId']);
        self::assertTrue($result->value()['active']);
    }

    #[Test]
    public function fallsBackToInactiveWhenActiveMissing(): void
    {
        $data = $this->validData();
        unset($data['active']);

        $result = $this->filter()->validate($data);

        self::assertTrue($result->valid());
        self::assertFalse($result->value()['active']);
    }

    #[Test]
    public function rejectsInvalidEmail(): void
    {
        $result = $this->filter()->validate([
            'id'        => '42',
            'firstName' => 'Jane',
            'lastName'  => 'Doe',
            'email'     => 'not-an-email',
            'roleId'    => 'Member',
        ]);

        self::assertFalse($result->valid());
        self::assertArrayHasKey('email', $result->getMessages());
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
    public function rejectsMissingRequiredFields(): void
    {
        $result = $this->filter()->validate([
            'id' => '42',
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
    public function trimsLastName(): void
    {
        $data             = $this->validData();
        $data['lastName'] = ' Doe ';

        $result = $this->filter()->validate($data);

        self::assertSame('Doe', $result->value()['lastName']);
    }

    #[Test]
    public function trimsStringRoleId(): void
    {
        $data           = $this->validData();
        $data['roleId'] = ' Member ';

        $result = $this->filter()->validate($data);

        self::assertSame('Member', $result->value()['roleId']);
    }

    private function filter(): UpdateUserDataFilter
    {
        return InputFilterHelper::updateUserDataFilter();
    }

    /** @return array<string, mixed> */
    private function validData(): array
    {
        return [
            'id'        => '42',
            'firstName' => 'Jane',
            'lastName'  => 'Doe',
            'email'     => 'jane@example.com',
            'roleId'    => 'Member',
            'active'    => '1',
        ];
    }
}
