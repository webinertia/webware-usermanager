<?php

declare(strict_types=1);

namespace Webware\UserManager\Validator\Container;

use Laminas\Validator\Exception\ExceptionInterface;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Validator\NoRecordExists;
use PhpDb\Validator\Options;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Builds the email-uniqueness validator with the container's database adapter.
 *
 * `NoRecordExists` throws `Adapter option missing.` unless an adapter is passed, and the
 * plugin manager's `InvokableFactory` passes none, so the adapter is injected here. The
 * table and field default to the user row's email address; call-site options win.
 *
 * @import-type OptionsArgument from Options
 *
 * @internal
 */
final readonly class NoRecordExistsFactory
{
    /**
     * @param OptionsArgument|null $options
     *
     * @throws ContainerExceptionInterface
     * @throws ExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(
        ContainerInterface $container,
        string $_requestedName,
        ?array $options = null,
    ): NoRecordExists {
        // The options type declares the concrete Adapter while the container binds
        // AdapterInterface, so the assertion below carries the concrete class.
        /** @var Adapter $adapter */
        $adapter = $container->get(AdapterInterface::class);

        return new NoRecordExists(options: [
            'adapter' => $adapter,
            'table'   => 'user',
            'field'   => 'email',
            ...($options ?? []),
        ]);
    }
}
