<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Admin\RequestHandler;

use InvalidArgumentException;
use Laminas\Diactoros\Exception\ExceptionInterface as DiactorosException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Htmx\Response\Header;
use Webware\UserManager\Http\RequestHandler\UserListHandler;

/**
 * The single render site for the admin edit-user response.
 *
 * Delegates to UserListHandler, which renders the list from the view model
 * UserListMiddleware attached, and points the browser back at the list. The
 * closeModal trigger comes from UserListHandler, so it is sent only when the
 * command reported success - a validation failure leaves the modal open.
 */
final class UpdateUserHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly UserListHandler $listHandler,
        private readonly string $listUrl,
    ) {}

    /**
     * @throws DiactorosException
     * @throws InvalidArgumentException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->listHandler->handle($request)
            ->withHeader(Header::PushUrl->value, $this->listUrl);
    }
}
