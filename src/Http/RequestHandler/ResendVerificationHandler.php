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
 * Render-only handler for the resend-verification flow. All token regeneration
 * and email dispatch happens in ProcessResendVerificationMiddleware.
 */
final class ResendVerificationHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template,
        private readonly string $loginUrl,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array{error?: string, sent?: bool, redirect?: bool}|null $templateParams */
        $templateParams = $request->getAttribute(self::class);

        if (null === $templateParams) {
            return new HtmlResponse($this->template->render('user::resend-verification'));
        }

        if ($templateParams['redirect'] ?? false) {
            return new RedirectResponse($this->loginUrl);
        }

        return new HtmlResponse($this->template->render('user::resend-verification', $templateParams));
    }
}
