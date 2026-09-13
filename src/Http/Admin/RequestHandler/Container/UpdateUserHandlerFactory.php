<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use Psl\Type;
use Psl\Type\Exception\ExceptionInterface as PslTypeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\UserManager\Http\Admin\RequestHandler\UpdateUserHandler;

final class UpdateUserHandlerFactory
{
    /**
     * @throws PslTypeException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): UpdateUserHandler
    {
        $templateRenderer = $container->get(TemplateRendererInterface::class);
        Type\instance_of(TemplateRendererInterface::class)->assert($templateRenderer);
        return new UpdateUserHandler($templateRenderer);
    }
}
