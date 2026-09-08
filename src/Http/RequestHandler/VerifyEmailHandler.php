<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\RequestHandler;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Template\TemplateRendererInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Render-only handler for the email verification flow. All token resolution
 * and activation happens in ProcessVerifyEmailMiddleware.
 */
final class VerifyEmailHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template,
        private readonly string $loginUrl,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array{error?: string, expired?: bool}|null $viewModel */
        $viewModel = $request->getAttribute(self::class);

        if (null !== $viewModel) {
            return new HtmlResponse(
                $this->template->render('user::verify-email', $viewModel),
            );
        }

        return new RedirectResponse($this->loginUrl);
    }
}
