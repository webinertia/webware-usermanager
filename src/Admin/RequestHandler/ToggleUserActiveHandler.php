<?php

declare(strict_types=1);

namespace Webware\UserManager\Admin\RequestHandler;

use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Core\UserInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageStatus;

final class ToggleUserActiveHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var CommandResult|null $result */
        $result = $request->getAttribute(CommandResult::class);

        if (! $result instanceof CommandResult || $result->getStatus() !== MessageStatus::Success) {
            return new HtmlResponse('', 422);
        }

        $user = $result->getResult();

        if (! $user instanceof UserInterface) {
            return new HtmlResponse('', 404);
        }

        return new HtmlResponse($this->template->render('user::partials/user-row', [
            'user'   => $user,
            'layout' => false,
            'body'   => false,
        ]));
    }
}
