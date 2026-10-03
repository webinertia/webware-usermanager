<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\RequestHandler;

use Laminas\Diactoros\Exception\ExceptionInterface as DiactorosException;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Htmx\Response\Header;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Http\Middleware\UserListMiddleware;

use function json_encode;

/**
 * Renders the user list from the view model UserListMiddleware attached.
 *
 * Render-only: the data is assembled by the middleware that runs ahead of this
 * handler in the pipeline. The closeModal trigger is added only when a command
 * in the same pipeline reported success, which is what closes the modal on the
 * write routes while leaving it open when a validation failed.
 */
final class UserListHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template,
    ) {}

    /**
     * @throws DiactorosException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array{users: list<\Webware\UserManager\Entity\User>} $viewModel */
        $viewModel = $request->getAttribute(UserListMiddleware::class, ['users' => []]);

        $response = new HtmlResponse($this->template->render('user::list-users', $viewModel));

        /** @var CommandResult|null $commandResult */
        $commandResult = $request->getAttribute(CommandResult::class);
        if ($commandResult instanceof CommandResult && $commandResult->getStatus() === MessageStatus::Success) {
            $response = $response->withHeader(Header::Trigger->value, json_encode(['closeModal' => null]));
        }

        return $response;
    }
}
