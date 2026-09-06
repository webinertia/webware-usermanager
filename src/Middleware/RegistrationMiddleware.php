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
use Webware\UserManager\InputFilter\UserDataFilter;
use Webware\UserManager\InputFilter\ValidationGroupTrait;

use function array_merge;
use function json_encode;

final class RegistrationMiddleware implements MiddlewareInterface
{
    use ValidationGroupTrait;

    const string DEFAULT_ROLE_ID = 'Member';
    const array DEFAULT_ROLE    = ['Member'];

    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly TemplateRendererInterface $template,
        private readonly UserDataFilter $filter,
    ) {}

    /**
     * @throws InvalidHopsValueException
     */
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $data = array_merge(
            $request->getParsedBody(),
            [
                'verificationToken' => Uuid::uuid7()->toString(),
                'roleId'            => json_encode(self::DEFAULT_ROLE),
                'active'            => '0',
            ],
        );

        $this->filter->setValidationGroup(self::REGISTRATION_VALIDATION_GROUP);
        $this->filter->setData($data);

        if (! $this->filter->isValid()) {
            return new HtmlResponse(
                $this->template->render('user::registration', ['errors' => $this->filter->getMessages()]),
                422,
            );
        }

        $values = $this->filter->getValues();
        unset($values['confirmPasswordHash']);

        $result = $this->messageBus->handle(
            new CreateUserCommand(...$values),
        );

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
