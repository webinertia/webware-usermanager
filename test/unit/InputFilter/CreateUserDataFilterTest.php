<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\InputFilter;

use Laminas\Filter\FilterPluginManager;
use Laminas\InputFilter;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Validator\ConfigProvider as ValidatorConfigProvider;
use Laminas\Validator\ValidatorInterface;
use Laminas\Validator\ValidatorPluginManager;
use PhpDb\Validator\NoRecordExists;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\InputFilter\CreateUserDataFilter;
use Webware\UserManager\Validator\AssignableRoleValidator;

#[CoversClass(CreateUserDataFilter::class)]
#[CoversMethod(CreateUserDataFilter::class, 'init')]
final class CreateUserDataFilterTest extends TestCase
{
    /**
     * @return array<string, array{field: string}>
     */
    public static function requiredFieldProvider(): array
    {
        return [
            'firstName' => ['field' => 'firstName'],
            'lastName'  => ['field' => 'lastName'],
            'email'     => ['field' => 'email'],
            'roleId'    => ['field' => 'roleId'],
        ];
    }

    #[Test]
    public function acceptsAnEmailThatDoesNotExistYet(): void
    {
        $uniqueness = $this->createMock(ValidatorInterface::class);
        $uniqueness->method('getMessages')->willReturn([]);
        $uniqueness->expects($this->once())
            ->method('isValid')
            ->with('jane@example.com')
            ->willReturn(true);

        $result = $this->filter(uniqueness: $uniqueness)->validate($this->validData());

        self::assertTrue($result->valid());
    }

    #[Test]
    public function acceptsValidCreateDataAndNormalisesEveryField(): void
    {
        $result = $this->filter()->validate([
            ...$this->validData(),
            'firstName' => ' Jane ',
            'lastName'  => ' Doe ',
            'email'     => ' Jane@Example.COM ',
            'roleId'    => ' Member ',
        ]);

        self::assertTrue($result->valid());
        self::assertSame('Jane', $result->value()['firstName']);
        self::assertSame('Doe', $result->value()['lastName']);
        self::assertSame('jane@example.com', $result->value()['email']);
        self::assertSame('Member', $result->value()['roleId']);
    }

    #[Test]
    public function ignoresServerControlledFields(): void
    {
        $result = $this->filter()->validate([
            ...$this->validData(),
            'passwordHash' => 'plaintext',
            'active'       => '1',
        ]);

        self::assertTrue($result->valid());
        self::assertArrayNotHasKey('passwordHash', $result->value());
        self::assertArrayNotHasKey('active', $result->value());
    }

    #[Test]
    #[DataProvider('requiredFieldProvider')]
    public function rejectsAMissingRequiredField(string $field): void
    {
        $data = $this->validData();
        unset($data[$field]);

        $result = $this->filter()->validate($data);

        self::assertFalse($result->valid());
        self::assertArrayHasKey($field, $result->getMessages());
    }

    #[Test]
    public function rejectsAnEmailThatAlreadyExists(): void
    {
        $uniqueness = $this->createMock(ValidatorInterface::class);
        $uniqueness->method('getMessages')
            ->willReturn(['recordFound' => 'A record matching the input was found']);
        $uniqueness->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $result = $this->filter(uniqueness: $uniqueness)->validate($this->validData());

        self::assertFalse($result->valid());
        self::assertArrayHasKey('email', $result->getMessages());
    }

    #[Test]
    public function rejectsAnInvalidEmail(): void
    {
        $result = $this->filter()->validate([...$this->validData(), 'email' => 'not-an-email']);

        self::assertFalse($result->valid());
        self::assertArrayHasKey('email', $result->getMessages());
    }

    #[Test]
    public function rejectsARoleOutsideTheAssignableSet(): void
    {
        $result = $this->filter()->validate([...$this->validData(), 'roleId' => 'Developer']);

        self::assertFalse($result->valid());
        self::assertArrayHasKey('roleId', $result->getMessages());
    }

    #[Test]
    public function rejectsEveryRoleWhenTheContextCarriesNoAssignableRoles(): void
    {
        $data = $this->validData();
        unset($data['assignableRoles']);

        $result = $this->filter()->validate($data);

        self::assertFalse($result->valid());
        self::assertArrayHasKey('roleId', $result->getMessages());
    }

    private function filter(?ValidatorInterface $uniqueness = null): CreateUserDataFilter
    {
        $container = new ServiceManager(new ValidatorConfigProvider()->getDependencyConfig());

        $validators = new ValidatorPluginManager($container);

        $container->setService(FilterPluginManager::class, new FilterPluginManager($container));
        $container->setService(ValidatorPluginManager::class, $validators);
        $container->setService(
            InputFilter\InputFilterPluginManager::class,
            new InputFilter\InputFilterPluginManager($container),
        );

        $defaultUniqueness = $this->createStub(ValidatorInterface::class);
        $defaultUniqueness->method('isValid')->willReturn(true);

        $validators->setService(NoRecordExists::class, $uniqueness ?? $defaultUniqueness);
        $validators->setService(AssignableRoleValidator::class, new AssignableRoleValidator());

        $filter = new CreateUserDataFilter(InputFilter\Factory::new($container));
        $filter->init();

        return $filter;
    }

    /**
     * @return array<string, mixed>
     */
    private function validData(): array
    {
        return [
            'firstName'       => 'Jane',
            'lastName'        => 'Doe',
            'email'           => 'jane@example.com',
            'roleId'          => 'Member',
            'assignableRoles' => ['Member', 'Administrator'],
        ];
    }
}
