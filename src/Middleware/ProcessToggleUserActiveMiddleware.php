<?php

declare(strict_types=1);

/**
 * This file is part of the Webware Farmers Store Inventory package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webware\UserManager\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Core\Http\Middleware\HttpMethodProcessorTrait;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Command\ToggleUserActiveCommand;

use function filter_var;

use const FILTER_VALIDATE_INT;

final readonly class ProcessToggleUserActiveMiddleware implements MiddlewareInterface
{
    use HttpMethodProcessorTrait;

    public function __construct(
        private MessageBusInterface $messageBus,
    ) {}

    public function processPost(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $id = filter_var($request->getAttribute('id'), FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);

        $result = $this->messageBus->handle(new ToggleUserActiveCommand($id));

        return $handler->handle($request->withAttribute(CommandResult::class, $result));
    }
}
