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
use Webware\Core\UserInterface;
use Webware\Htmx\Attribute;
use Webware\Htmx\Response\Header;
use Webware\Message\SystemMessengerInterface;

use function in_array;

/**
 * Renders the login page.
 *
 * GET: renders the form.
 * POST failure: LoginMiddleware passes through on bad credentials; this handler re-renders with errors.
 * POST success: LoginMiddleware redirects before this handler is reached.
 */
final class LoginHandler implements RequestHandlerInterface
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
        /** @var UserInterface $user */
        $user = $request->getAttribute(UserInterface::class);

        // The Guest principal carries only the Guest role, so any other role means an
        // authenticated session. Roles are read instead of the identity because an entity
        // that was never hydrated has no identity to report.
        $roles = $user->getRoles() ?? [];

        if (! in_array(UserInterface::GUEST_ROLE, $roles, strict: true)) {
            // Authenticated — redirect; HTMX boosted forms need HX-Redirect
            if ($request->getAttribute(Attribute::Request->value) === true) {
                return new EmptyResponse(200, [Header::Redirect->value => '/']);
            }

            return new RedirectResponse('/');
        }

        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);
        $messages  = $messenger?->getMessages() ?? [];

        return new HtmlResponse($this->template->render('user::login', [
            'flashMessages' => $messages,
        ]));
    }
}
