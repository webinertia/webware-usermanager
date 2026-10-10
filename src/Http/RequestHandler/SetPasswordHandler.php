<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\RequestHandler;

use Laminas\Diactoros\Exception\ExceptionInterface as DiactorosException;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Template\TemplateRendererInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Renders the set-password page. Token resolution, validation and the two
 * commands all happen in {@see \Webware\UserManager\Http\Middleware\ProcessSetPasswordMiddleware}.
 */
final class SetPasswordHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template,
        private readonly string $loginUrl,
    ) {}

    /**
     * @throws DiactorosException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array{error?: string, expired?: bool, success?: bool, token?: string, errors?: array<string, list<string>>, status?: int}|null $params */
        $params = $request->getAttribute(self::class);

        if (null === $params || ($params['success'] ?? false)) {
            return new RedirectResponse($this->loginUrl);
        }

        return new HtmlResponse(
            $this->template->render('user::set-password', $params),
            $params['status'] ?? 200,
        );
    }
}
