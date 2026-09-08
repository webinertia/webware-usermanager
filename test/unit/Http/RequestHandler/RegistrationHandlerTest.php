<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Http\RequestHandler;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\Command\CommandInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageStatus;
use Webware\UserManager\Http\RequestHandler\RegistrationHandler;

#[CoversClass(RegistrationHandler::class)]
#[CoversMethod(RegistrationHandler::class, '__construct')]
#[CoversMethod(RegistrationHandler::class, 'handle')]
final class RegistrationHandlerTest extends TestCase
{
    #[Test]
    public function redirectsOnSuccessfulRegistration(): void
    {
        $handler = new RegistrationHandler(
            template: $this->createStub(TemplateRendererInterface::class),
            loginUrl: '/user.manager/login',
        );

        $result  = new CommandResult($this->createStub(CommandInterface::class), MessageStatus::Success, null);
        $request = new ServerRequest()->withAttribute(CommandResult::class, $result);

        $response = $handler->handle($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/user.manager/login', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function rendersFormOnFailedRegistration(): void
    {
        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::registration')
            ->willReturn('<form>');

        $handler = new RegistrationHandler(
            template: $template,
            loginUrl: '/user.manager/login',
        );

        $result  = new CommandResult($this->createStub(CommandInterface::class), MessageStatus::Failure, 'nope');
        $request = new ServerRequest()->withAttribute(CommandResult::class, $result);

        $response = $handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    #[Test]
    public function rendersFormWhenNoResultPresent(): void
    {
        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->with('user::registration')
            ->willReturn('<form>');

        $handler = new RegistrationHandler(
            template: $template,
            loginUrl: '/user.manager/login',
        );

        $response = $handler->handle(new ServerRequest());

        self::assertInstanceOf(HtmlResponse::class, $response);
        self::assertSame('<form>', (string) $response->getBody());
    }
}
