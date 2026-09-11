<?php

declare(strict_types=1);

namespace Webware\UserManager\QueryHandler;

use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\MessageBus\QueryHandlerInterface;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Query\FetchUsersQuery;
use Webware\UserManager\Repository\UserRepositoryInterface;

final readonly class FetchUsersHandler implements QueryHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {}

    public function handle(FetchUsersQuery $query): QueryResult
    {
        $resultSet = $this->users->findAll();

        /** @var list<User> $users */
        $users = [];

        /** @var User $user */
        foreach ($resultSet as $user) {
            $users[] = $user;
        }

        return new QueryResult($query, MessageStatus::Success, $users);
    }
}
