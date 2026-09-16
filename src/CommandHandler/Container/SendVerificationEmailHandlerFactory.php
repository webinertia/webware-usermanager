<?php

declare(strict_types=1);

namespace Webware\UserManager\CommandHandler\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Mailer\MailerInterface;
use Webware\UserManager\CommandHandler\SendVerificationEmailHandler;

final class SendVerificationEmailHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): SendVerificationEmailHandler
    {
        return new SendVerificationEmailHandler(
            mailer: $container->get(MailerInterface::class),
        );
    }
}
