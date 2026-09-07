<?php

declare(strict_types=1);

namespace Webware\UserManager\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Core\Http\Middleware\HttpMethodProcessorTrait;
use Webware\Message\Exception\InvalidHopsValueException;
use Webware\Message\SystemMessengerInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Command\UpdateUserCommand;
use Webware\UserManager\InputFilter\UpdateUserDataFilter;

use function array_merge;
use function is_array;

final readonly class ProcessUpdateUserMiddleware implements MiddlewareInterface
{
    use HttpMethodProcessorTrait;

    public function __construct(
        private MessageBusInterface $messageBus,
        private UpdateUserDataFilter $filter,
    ) {}

    /**
     * @throws InvalidHopsValueException
     */
    public function processPatch(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);
        $body      = $request->getParsedBody();
        $data      = array_merge(
            is_array($body) ? $body : [],
            ['id' => $request->getAttribute('id')],
        );

        $filterResult = $this->filter->validate($data);

        if (! $filterResult->valid()) {
            $messenger?->warning($this->filter->getSystemMessage($filterResult->getMessages()));

            return $handler->handle($request);
        }

        /** @var array{id: int, firstName: string, lastName: string, email: string, roleId: array<string>, active: bool} $values */
        $values = $filterResult->value();

        $result = $this->messageBus->handle(new UpdateUserCommand(
            id       : $values['id'],
            firstName: $values['firstName'],
            lastName : $values['lastName'],
            email    : $values['email'],
            roleId   : $values['roleId'],
            active   : $values['active'],
        ));

        if ($result->getStatus() === MessageStatus::Success) {
            $messenger?->success('User updated.', hops: 0, now: true);
        } else {
            $messenger?->danger('User could not be updated. Please try again.', hops: 0, now: true);
        }

        return $handler->handle($request->withAttribute(CommandResult::class, $result));
    }
}
