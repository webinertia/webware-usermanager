<?php

declare(strict_types=1);

namespace Webware\UserManager\Http\Admin\RequestHandler;

use InvalidArgumentException;
use Laminas\Diactoros\Exception\ExceptionInterface as DiactorosException;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Htmx\Response\Header;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Http\Admin\CreateUserState;
use Webware\UserManager\Http\RequestHandler\UserListHandler;

use function json_encode;

/**
 * The single render site for the admin create-user form.
 *
 * The middleware has already validated and dispatched; this handler only turns
 * the {@see CreateUserState} it left behind into a response: the refreshed user
 * list on success, the form again with 422 on a validation failure, and 500 when
 * the command failed.
 */
final class CreateUserHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template,
        private readonly UserListHandler $listHandler,
        private readonly string $listUrl,
    ) {}

    /**
     * @throws DiactorosException
     * @throws InvalidArgumentException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var CreateUserState|null $state */
        $state = $request->getAttribute(CreateUserState::class);

        if (
            $state instanceof CreateUserState
            && $state->result instanceof CommandResult
            && MessageStatus::Success === $state->result->getStatus()
        ) {
            return $this->listHandler->handle($request)
                ->withHeader(Header::PushUrl->value, $this->listUrl)
                ->withHeader(Header::Trigger->value, (string) json_encode(['closeModal' => null]));
        }

        $assignableRoles = $state instanceof CreateUserState ? $state->assignableRoles : [];
        $errors          = $state instanceof CreateUserState ? $state->errors : [];
        $old             = $state instanceof CreateUserState ? $state->old : [];
        $status          = $this->statusFor($state);

        $response = new HtmlResponse(
            $this->template->render('user::create-user-modal', [
                'assignableRoles' => $assignableRoles,
                'errors'          => $errors,
                'old'             => $old,
                'layout'          => false,
                'body'            => false,
            ]),
            $status,
        );

        // The form posts into `main`, so a failure render has to be aimed back at
        // the modal it came from.
        return match ($status) {
            422, 500 => $response->withHeader(Header::Retarget->value, '#sharedModalDialog')
                ->withHeader(Header::Reswap->value, 'innerHTML'),
            default  => $response,
        };
    }

    private function statusFor(?CreateUserState $state): int
    {
        if (! $state instanceof CreateUserState) {
            return 200;
        }

        return match (true) {
            [] !== $state->errors => 422,
            $state->result instanceof CommandResult => 500,
            default => 200,
        };
    }
}
