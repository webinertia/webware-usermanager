<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Admin\RequestHandler;

use Laminas\Diactoros\Exception\ExceptionInterface as DiactorosException;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Override;
use Psl\Type\Exception\AssertException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\UserManager\Http\Admin\AssignableRolesProvider;

/**
 * Returns the create-user modal fragment for the admin user list.
 *
 * The select is fed by the roles the actor may assign, so the form only ever
 * offers a role the actor is allowed to hand out. An actor with no role gets an
 * empty list, which fails closed: nothing can be submitted successfully.
 */
final class CreateUserModalHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template,
        private readonly AssignableRolesProvider $assignableRoles,
    ) {}

    /**
     * @throws DiactorosException
     * @throws AssertException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->template->render('user::create-user-modal', [
            'assignableRoles' => $this->assignableRoles->forRequest($request),
            'errors'          => [],
            'old'             => [],
            'layout'          => false,
            'body'            => false,
        ]));
    }
}
