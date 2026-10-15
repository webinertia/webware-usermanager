<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\RequestHandler;

use Laminas\Diactoros\Exception\ExceptionInterface as DiactorosException;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Template\TemplateRendererInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Core\Role;
use Webware\Core\UserInterface;
use Webware\Htmx\Attribute;
use Webware\Htmx\Response\Header;
use Webware\Message\SystemMessengerInterface;

/**
 * Renders the login page.
 *
 * GET: renders the form.
 * POST failure: LoginMiddleware passes through on bad credentials; this handler re-renders with errors.
 * POST success: LoginMiddleware redirects before this handler is reached.
 *
 * The POST body field names are resolved from configuration and handed to the
 * template, so the form posts exactly what LoginMiddleware reads back.
 */
final class LoginHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template,
        private readonly string $credentialField,
        private readonly string $passwordField,
    ) {}

    /**
     * @throws DiactorosException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserInterface $user */
        $user = $request->getAttribute(UserInterface::class);

        // The guest principal is built from Role::Guest alone, so the role is the attribute
        // every principal carries; any other role is a session hydrated from a row.
        if (Role::Guest->value !== $user->getRoleId()) {
            // Authenticated - redirect; HTMX boosted forms need HX-Redirect
            if ($request->getAttribute(Attribute::Request->value) === true) {
                return new EmptyResponse(200, [Header::Redirect->value => '/']);
            }

            return new RedirectResponse('/');
        }

        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);
        $messages  = $messenger?->getMessages() ?? [];

        return new HtmlResponse($this->template->render('user::login', [
            'credentialField' => $this->credentialField,
            'passwordField'   => $this->passwordField,
            'flashMessages'   => $messages,
        ]));
    }
}
