<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\QueryHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Query\CheckUserActiveQuery;
use Webware\UserManager\QueryHandler\CheckUserActiveHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;

#[CoversClass(CheckUserActiveHandler::class)]
#[CoversMethod(CheckUserActiveHandler::class, '__construct')]
#[CoversMethod(CheckUserActiveHandler::class, 'handle')]
final class CheckUserActiveHandlerTest extends TestCase
{
    #[Test]
    public function handleReturnsActiveStatus(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('checkStatus')->willReturn(true);

        $handler = new CheckUserActiveHandler($users);
        $result  = $handler->handle(new CheckUserActiveQuery(id: 7));

        self::assertSame(MessageStatus::Success, $result->getStatus());
        self::assertTrue($result->getResult());
    }
}
