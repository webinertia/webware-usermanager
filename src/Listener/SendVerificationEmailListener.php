<?php

declare(strict_types=1);

namespace Webware\UserManager\Listener;

use InvalidArgumentException;
use Mezzio\Helper\Exception\ExceptionInterface as HelperException;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Command\CreateUserCommand;
use Webware\UserManager\Command\SendVerificationEmailCommand;
use Webware\UserManager\Event\SendVerificationEmailEvent;
use Webware\UserManager\View\Helper\UserUrl;

use function rtrim;

final class SendVerificationEmailListener
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly string $baseUrl,
        private readonly string $verificationSubject,
        private readonly UserUrl $userUrl,
    ) {}

    /**
     * An account created without a password gets the set-password link instead,
     * so the link and the page agree on what happens next.
     */
    private function routeName(CreateUserCommand $command): string
    {
        return 0 === $command->passwordSetRequired ? 'verify.email.read' : 'set.password.read';
    }

    /**
     * @throws HelperException
     * @throws InvalidArgumentException
     */
    public function __invoke(SendVerificationEmailEvent $event): void
    {
        $command         = $event->getTarget();
        $token           = $command->verificationToken;
        $verificationUrl = rtrim(
            string    : $this->baseUrl,
            characters: '/',
        )
        . ($this->userUrl)($this->routeName($command), ['token' => $token]);

        $this->messageBus->handle(new SendVerificationEmailCommand(
            to             : $event->getEmail(),
            firstName      : $command->firstName,
            verificationUrl: $verificationUrl,
            subject        : $this->verificationSubject,
        ));
    }
}
