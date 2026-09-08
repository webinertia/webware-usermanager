<?php

declare(strict_types=1);

namespace Webware\UserManager\QueryHandler;

use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\MessageBus\QueryHandlerInterface;
use Webware\UserManager\Query\FetchUserByEmail;
use Webware\UserManager\Repository\UserRepositoryInterface;

final readonly class FetchUserByEmailHandler implements QueryHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {}

    public function handle(FetchUserByEmail $query): QueryResult
    {
        $user = $this->users->findByEmail($query->email);

        return null === $user
            ? new QueryResult($query, MessageStatus::Failure, null)
            : new QueryResult($query, MessageStatus::Success, $user);
    }
}
