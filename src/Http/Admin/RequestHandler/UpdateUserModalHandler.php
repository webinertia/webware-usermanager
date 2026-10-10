<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Admin\RequestHandler;

use Laminas\Diactoros\Exception\ExceptionInterface as DiactorosException;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Core\UserInterface;
use Webware\UserManager\Http\Admin\Middleware\UpdateUserModalMiddleware;

/**
 * Renders the update-user modal from the view model UpdateUserModalMiddleware
 * attached.
 *
 * Render-only: the user and the assignable roles are assembled by the
 * middleware that runs ahead of this handler in the pipeline. The 404 is kept
 * here because it is a render decision - the attribute carries null when the id
 * is unusable or no such user exists.
 */
final class UpdateUserModalHandler implements RequestHandlerInterface
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
        /** @var array{user: ?UserInterface, assignableRoles: list<string>} $viewModel */
        $viewModel = $request->getAttribute(UpdateUserModalMiddleware::class, [
            'user'            => null,
            'assignableRoles' => [],
        ]);

        if (! $viewModel['user'] instanceof UserInterface) {
            return new HtmlResponse('', 404);
        }

        return new HtmlResponse($this->template->render('user::update-user-modal', [
            'user'            => $viewModel['user'],
            'assignableRoles' => $viewModel['assignableRoles'],
            'layout'          => false,
            'body'            => false,
        ]));
    }
}
