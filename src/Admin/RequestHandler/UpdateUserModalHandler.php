<?php

declare(strict_types=1);

namespace Webware\UserManager\Admin\RequestHandler;

use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\UserManager\Repository\UserRepositoryInterface;

use function filter_var;

final class UpdateUserModalHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id   = filter_var($request->getAttribute('id'), FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
        $user = $this->users->findById($id);

        if ($user === null) {
            return new HtmlResponse('', 404);
        }

        return new HtmlResponse($this->template->render('user::update-user-modal', [
            'user'   => $user,
            'layout' => false,
            'body'   => false,
        ]));
    }
}
