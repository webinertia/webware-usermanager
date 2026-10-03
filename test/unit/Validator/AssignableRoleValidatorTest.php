<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Validator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\UserManager\Validator\AssignableRoleValidator;

#[CoversClass(AssignableRoleValidator::class)]
#[CoversMethod(AssignableRoleValidator::class, 'isValid')]
final class AssignableRoleValidatorTest extends TestCase
{
    #[Test]
    public function acceptsARoleFromTheAssignableSet(): void
    {
        $validator = new AssignableRoleValidator();

        self::assertTrue($validator->isValid('Member', ['assignableRoles' => ['Member', 'Administrator']]));
        self::assertSame([], $validator->getMessages());
    }

    #[Test]
    public function clearsPreviousMessagesOnASuccessfulValidation(): void
    {
        $validator = new AssignableRoleValidator();
        $validator->isValid('Developer', ['assignableRoles' => ['Member']]);
        self::assertArrayHasKey(AssignableRoleValidator::NOT_ASSIGNABLE, $validator->getMessages());

        self::assertTrue($validator->isValid('Member', ['assignableRoles' => ['Member']]));
        self::assertSame([], $validator->getMessages());
    }

    #[Test]
    public function rejectsANonStringValue(): void
    {
        $validator = new AssignableRoleValidator();

        self::assertFalse($validator->isValid(['Member'], ['assignableRoles' => ['Member']]));
        self::assertFalse($validator->isValid(null, ['assignableRoles' => ['Member']]));
    }

    #[Test]
    public function rejectsARoleOutsideTheAssignableSet(): void
    {
        $validator = new AssignableRoleValidator();

        self::assertFalse($validator->isValid('Developer', ['assignableRoles' => ['Member', 'Administrator']]));
        self::assertArrayHasKey(AssignableRoleValidator::NOT_ASSIGNABLE, $validator->getMessages());
    }

    #[Test]
    public function rejectsEveryRoleWhenTheContextCarriesNoAssignableRoles(): void
    {
        $validator = new AssignableRoleValidator();

        self::assertFalse($validator->isValid('Member'));
        self::assertFalse($validator->isValid('Member', []));
        self::assertFalse($validator->isValid('Member', ['assignableRoles' => []]));
    }

    #[Test]
    public function reportsTheRejectionWithItsMessageTemplate(): void
    {
        $validator = new AssignableRoleValidator();
        $validator->isValid('Developer', ['assignableRoles' => ['Member']]);

        self::assertSame(
            [AssignableRoleValidator::NOT_ASSIGNABLE => 'The selected role is not one you may assign.'],
            $validator->getMessages(),
        );
    }
}
