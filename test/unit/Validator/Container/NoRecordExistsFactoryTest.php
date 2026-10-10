<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Validator\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Validator\NoRecordExists;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\UserManager\Validator\Container\NoRecordExistsFactory;

#[CoversClass(NoRecordExistsFactory::class)]
#[CoversMethod(NoRecordExistsFactory::class, '__invoke')]
final class NoRecordExistsFactoryTest extends TestCase
{
    #[Test]
    public function buildsTheUserEmailValidatorWithTheContainerAdapter(): void
    {
        $adapter = $this->createStub(AdapterInterface::class);

        $validator = new NoRecordExistsFactory()(
            container     : $this->containerReturning($adapter),
            _requestedName: NoRecordExists::class,
        );

        self::assertSame($adapter, $validator->getAdapter());
        self::assertSame('user', $validator->getTable());
        self::assertSame('email', $validator->getField());
    }

    #[Test]
    public function letsCallSiteOptionsOverrideTheDefaults(): void
    {
        $adapter = $this->createStub(AdapterInterface::class);

        $validator = new NoRecordExistsFactory()(
            container     : $this->containerReturning($adapter),
            _requestedName: NoRecordExists::class,
            options       : [
                'table' => 'admin_user',
                'field' => 'username',
            ],
        );

        self::assertSame($adapter, $validator->getAdapter());
        self::assertSame('admin_user', $validator->getTable());
        self::assertSame('username', $validator->getField());
    }

    private function containerReturning(AdapterInterface $adapter): ContainerInterface
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturn($adapter);

        return $container;
    }
}
