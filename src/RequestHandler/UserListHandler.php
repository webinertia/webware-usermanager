<?php

declare(strict_types=1);

namespace Webware\UserManager\RequestHandler;

use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Htmx\Response\Header;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Repository\UserRepositoryInterface;

use function json_encode;

final class UserListHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = new HtmlResponse($this->template->render('user::list-users', [
            'users' => $this->users->findAll(),
        ]));

        $commandResult = $request->getAttribute(CommandResult::class);
        if ($commandResult instanceof CommandResult && $commandResult->getStatus() === MessageStatus::Success) {
            $response = $response->withHeader(Header::Trigger->value, json_encode(['closeModal' => null]));
        }

        return $response;
    }
}
