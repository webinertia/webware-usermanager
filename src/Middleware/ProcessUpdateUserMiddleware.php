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
use Webware\UserManager\InputFilter\UserDataFilter;
use Webware\UserManager\InputFilter\ValidationGroupTrait;

use function array_merge;

final readonly class ProcessUpdateUserMiddleware implements MiddlewareInterface
{
    use HttpMethodProcessorTrait;
    use ValidationGroupTrait;

    public function __construct(
        private MessageBusInterface $messageBus,
        private UserDataFilter $filter,
    ) {}

    /**
     * @throws InvalidHopsValueException
     */
    public function processPatch(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);
        $data      = array_merge(
            $request->getParsedBody(),
            ['id' => $request->getAttribute('id')],
        );

        $this->filter->setValidationGroup(self::UPDATE_VALIDATION_GROUP);
        $this->filter->setData($data);
        if (! $this->filter->isValid()) {
            $messenger?->warning($this->filter->getSystemMessage());
            return $handler->handle($request);
        }
        $command = new UpdateUserCommand(...$this->filter->getValues());

        $result = $this->messageBus->handle($command);

        if ($result->getStatus() === MessageStatus::Success) {
            $messenger?->success('User updated.', hops: 0, now: true);
        } else {
            $messenger?->danger('User could not be updated. Please try again.', hops: 0, now: true);
        }

        return $handler->handle($request->withAttribute(CommandResult::class, $result));
    }
}
