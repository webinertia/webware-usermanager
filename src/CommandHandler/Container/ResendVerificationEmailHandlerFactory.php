<?php

declare(strict_types=1);

namespace Webware\UserManager\CommandHandler\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Mailer\MailerInterface;
use Webware\UserManager\CommandHandler\ResendVerificationEmailHandler;

final class ResendVerificationEmailHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ResendVerificationEmailHandler
    {
        return new ResendVerificationEmailHandler(
            mailer: $container->get(MailerInterface::class),
        );
    }
}
