<?php

declare(strict_types=1);

namespace Webware\UserManager\Admin\Dashboard;

use Webware\Admin\Event\RegisterWidgetEvent;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Query\FetchUsers;

use function count;

final class RegisterWidgetListener
{
    public function __construct(
        private readonly string $resourceId,
        private readonly MessageBusInterface $messageBus,
    ) {}

    public function __invoke(RegisterWidgetEvent $event): void
    {
        /** @var list<User> $allUsers */
        $allUsers = $this->messageBus->handle(new FetchUsers())->getResult();

        $totalUsers    = count($allUsers);
        $activeUsers   = 0;
        $inactiveUsers = 0;

        foreach ($allUsers as $user) {
            if ($user->active) {
                $activeUsers++;
            } else {
                $inactiveUsers++;
            }
        }

        $event->registerWidget(new Widget(
            resourceId   : $this->resourceId,
            totalUsers   : $totalUsers,
            activeUsers  : $activeUsers,
            inactiveUsers: $inactiveUsers,
        ));
    }
}
