<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Admin\RequestHandler;

use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Core\UserInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Query\FetchUserByIdQuery;

use function filter_var;

final class UpdateUserModalHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template,
        private readonly MessageBusInterface $messageBus,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = filter_var($request->getAttribute('id'), FILTER_VALIDATE_INT);

        if (false === $id) {
            return new HtmlResponse('', 404);
        }

        $result = $this->messageBus->handle(new FetchUserByIdQuery(id: $id));

        if ($result->getStatus() === MessageStatus::Failure) {
            return new HtmlResponse('', 404);
        }

        /** @var UserInterface $user */
        $user = $result->getResult();

        return new HtmlResponse($this->template->render('user::update-user-modal', [
            'user'   => $user,
            'layout' => false,
            'body'   => false,
        ]));
    }
}
