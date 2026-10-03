<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Admin\Middleware;

use Psl\Type;
use Psl\Type\Exception\AssertException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Random\RandomException;
use Webware\Core\Http\Middleware\HttpMethodProcessorTrait;
use Webware\Core\UserInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\UserManager\Command\CreateUserCommand;
use Webware\UserManager\Http\Admin\CreateUserState;
use Webware\UserManager\InputFilter\CreateUserDataFilter;
use Webware\UserManager\Query\FetchAssignableRolesQuery;

use function array_filter;
use function array_map;
use function array_shift;
use function array_values;
use function bin2hex;
use function in_array;
use function is_array;
use function is_string;
use function password_hash;
use function random_bytes;

use const PASSWORD_DEFAULT;

/**
 * Validates and dispatches the admin create-user POST.
 *
 * GET only fetches the assignable roles for the modal. POST validates the body
 * against {@see CreateUserDataFilter} with the assignable roles supplied by the
 * server — never the client — and either leaves the failure on the request as a
 * {@see CreateUserState} or dispatches {@see CreateUserCommand}.
 *
 * An actor attribute that is missing or role-less yields an empty assignable
 * set, so a submitted role is rejected and logged: the endpoint fails closed.
 */
final readonly class ProcessCreateUserMiddleware implements MiddlewareInterface
{
    use HttpMethodProcessorTrait;

    public function __construct(
        private MessageBusInterface $messageBus,
        private CreateUserDataFilter $filter,
        private LoggerInterface $logger,
    ) {}

    /**
     * @throws AssertException
     * @throws RandomException
     */
    public function processPost(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $assignableRoles = $this->assignableRoles($request);
        $body            = $request->getParsedBody();

        /** @var array<string, mixed> $posted */
        $posted = is_array($body) ? $body : [];

        // The server-controlled key goes after the spread: the client's own list
        // must never be the one the validator reads.
        $filterResult = $this->filter->validate([
            ...$posted,
            'assignableRoles' => $assignableRoles,
        ]);

        $old = array_filter($posted, is_string(...));

        if (! $filterResult->valid()) {
            $this->warnAboutRejectedRole($old['roleId'] ?? '', $assignableRoles);

            /** @var array<string, array<array-key, string>> $messages */
            $messages = $filterResult->getMessages()->toArray();

            return $handler->handle($request->withAttribute(
                CreateUserState::class,
                new CreateUserState(
                    assignableRoles: $assignableRoles,
                    errors         : $this->errors($messages),
                    old            : $old,
                ),
            ));
        }

        /** @var array{firstName: string, lastName: string, email: string, roleId: string} $values */
        $values = $filterResult->value();

        /** @var CommandResult $result */
        $result = $this->messageBus->handle(new CreateUserCommand(
            firstName        : $values['firstName'],
            lastName         : $values['lastName'],
            // An unusable random hash: the account is inactive and the user sets
            // their own password from the verification email.
            passwordHash     : password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
            email            : $values['email'],
            roleId           : $values['roleId'],
            verificationToken: Uuid::uuid7()->toString(),
            active           : false,
        ));

        if (MessageStatus::Success === $result->getStatus()) {
            $this->logger->info('Created user', [
                'actor'  => $this->actorIdentity($request),
                'userId' => $result->getResult(),
                'roleId' => $values['roleId'],
            ]);
        }

        // The success/failure notification is sent centrally by NotificationMiddleware
        // from the CommandResult below. The handler reads the outcome from the state,
        // so both attributes are attached.
        return $handler->handle(
            $request->withAttribute(CommandResult::class, $result)
                ->withAttribute(
                    CreateUserState::class,
                    new CreateUserState(
                        assignableRoles: $assignableRoles,
                        old            : $old,
                        result         : $result,
                    ),
                ),
        );
    }

    private function actorIdentity(ServerRequestInterface $request): ?string
    {
        /** @var UserInterface|null $actor */
        $actor = $request->getAttribute(UserInterface::class);

        return $actor instanceof UserInterface ? $actor->getIdentity() : null;
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
     * The roles the actor may assign; an actor with no role gets none.
     *
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

    /**
     * @param array<string, array<array-key, string>> $messages
     *
     * @return array<string, list<string>>
     */
    private function errors(array $messages): array
    {
        return array_filter(array_map(array_values(...), $messages));
    }

    /**
     * @param list<string> $assignableRoles
     */
    private function warnAboutRejectedRole(string $submittedRoleId, array $assignableRoles): void
    {
        if ('' === $submittedRoleId || in_array($submittedRoleId, $assignableRoles, strict: true)) {
            return;
        }

        $this->logger->warning('Rejected a role outside the assignable set', [
            'roleId' => $submittedRoleId,
        ]);
    }
}
