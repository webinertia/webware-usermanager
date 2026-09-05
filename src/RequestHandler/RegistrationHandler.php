<?php

declare(strict_types=1);

namespace Webware\UserManager\RequestHandler;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Template\TemplateRendererInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageStatus;

final class RegistrationHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template,
        private readonly string $loginUrl,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var CommandResult $result */
        $result = $request->getAttribute(CommandResult::class);

        if (null !== $result && $result->getStatus() === MessageStatus::Success) {
            return new RedirectResponse($this->loginUrl);
        }

        return new HtmlResponse($this->template->render('user::registration'));
    }
}
