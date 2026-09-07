<?php

declare(strict_types=1);

namespace Webware\UserManager\Middleware;

use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Uuid;
use Webware\Message\Exception\InvalidHopsValueException;
use Webware\Message\SystemMessengerInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\CreateUserCommand;
use Webware\UserManager\InputFilter\RegistrationDataFilter;

use function array_merge;
use function is_array;
use function json_encode;

final class RegistrationMiddleware implements MiddlewareInterface
{
    const string DEFAULT_ROLE_ID = 'Member';
    const array DEFAULT_ROLE    = ['Member'];

    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly TemplateRendererInterface $template,
        private readonly RegistrationDataFilter $filter,
    ) {}

    /**
     * @throws InvalidHopsValueException
     */
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $body = $request->getParsedBody();
        $data = array_merge(
            is_array($body) ? $body : [],
            [
                'verificationToken' => Uuid::uuid7()->toString(),
                'roleId'            => json_encode(self::DEFAULT_ROLE),
                'active'            => '0',
            ],
        );

        $filterResult = $this->filter->validate($data);

        if (! $filterResult->valid()) {
            return new HtmlResponse(
                $this->template->render('user::registration', ['errors' => $filterResult->getMessages()->toArray()]),
                422,
            );
        }

        /** @var array{firstName: string, lastName: string, email: string, passwordHash: string, confirmPasswordHash: string, verificationToken: string, roleId: string, active: bool} $values */
        $values = $filterResult->value();
        unset($values['confirmPasswordHash']);

        $result = $this->messageBus->handle(new CreateUserCommand(
            firstName        : $values['firstName'],
            lastName         : $values['lastName'],
            passwordHash     : $values['passwordHash'],
            email            : $values['email'],
            roleId           : $values['roleId'],
            verificationToken: $values['verificationToken'],
            active           : $values['active'],
        ));

        if ($result->getStatus() === MessageStatus::Failure) {
            return new HtmlResponse(
                $this->template->render('user::registration', ['errors' => [$result->getResult()]]),
                500,
            );
        }

        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);
        $messenger?->success(
            'Registration successful! Please check your email to verify your account.',
            hops: 1,
            now: false,
        );

        return $handler->handle(
            $request->withAttribute(CommandResult::class, $result),
        );
    }
}
