<?php

declare(strict_types=1);

namespace Webware\UserManager\QueryHandler;

use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\MessageBus\QueryHandlerInterface;
use Webware\UserManager\Query\CheckUserActiveQuery;
use Webware\UserManager\Repository\UserRepositoryInterface;

final readonly class CheckUserActiveHandler implements QueryHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {}

    public function handle(CheckUserActiveQuery $query): QueryResult
    {
        return new QueryResult(
            $query,
            MessageStatus::Success,
            $this->users->checkStatus($query->id),
        );
    }
}
