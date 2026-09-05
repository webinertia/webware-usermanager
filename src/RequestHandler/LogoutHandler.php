<?php

declare(strict_types=1);

namespace Webware\UserManager\RequestHandler;

use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\RetrieveSession;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class LogoutHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly string $loginUrl,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = RetrieveSession::fromRequestOrNull($request);
        $session?->clear();

        return new RedirectResponse($this->loginUrl);
    }
}
