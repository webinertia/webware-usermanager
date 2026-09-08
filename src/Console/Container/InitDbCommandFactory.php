<?php

declare(strict_types=1);

namespace Webware\UserManager\Console\Container;

use PhpDb\Adapter\AdapterInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Exception\LogicException;
use Webware\UserManager\Console\InitDbCommand;

final readonly class InitDbCommandFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws LogicException
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): InitDbCommand
    {
        return new InitDbCommand(
            $container->get(AdapterInterface::class),
        );
    }
}
