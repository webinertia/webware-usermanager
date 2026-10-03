<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Admin\RequestHandler;

use Laminas\Diactoros\Exception\ExceptionInterface as DiactorosException;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Override;
use Psl\Type;
use Psl\Type\Exception\AssertException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Core\UserInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\Query\QueryResult;
use Webware\UserManager\Query\FetchAssignableRolesQuery;

use function array_shift;

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
        private readonly MessageBusInterface $messageBus,
    ) {}

    /**
     * @throws DiactorosException
     * @throws AssertException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->template->render('user::create-user-modal', [
            'assignableRoles' => $this->assignableRoles($request),
            'errors'          => [],
            'old'             => [],
            'layout'          => false,
            'body'            => false,
        ]));
    }

    /**
     * The actor's role, read through the component's own user contract.
     */
    private function actorRoleId(ServerRequestInterface $request): ?string
    {
        /** @var UserInterface|null $actor */
        $actor = $request->getAttribute(UserInterface::class);

        if (! $actor instanceof UserInterface) {
            return null;
        }

        $roles = [...$actor->getRoles()];

        return array_shift($roles);
    }

    /**
     * @throws AssertException
     *
     * @return list<string>
     */
    private function assignableRoles(ServerRequestInterface $request): array
    {
        $actorRoleId = $this->actorRoleId($request);

        if (null === $actorRoleId) {
            return [];
        }

        /** @var QueryResult $result */
        $result = $this->messageBus->handle(
            new FetchAssignableRolesQuery(actorRoleId: $actorRoleId),
        );

        return Type\vec(Type\string())->assert($result->getResult());
    }
}
