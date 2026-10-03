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
use Webware\UserManager\Http\RequestHandler\SetPasswordHandler;

use function bin2hex;
use function random_bytes;

#[CoversClass(SetPasswordHandler::class)]
#[CoversMethod(SetPasswordHandler::class, '__construct')]
#[CoversMethod(SetPasswordHandler::class, 'handle')]
final class SetPasswordHandlerTest extends TestCase
{
    #[Test]
    public function redirectsAfterASuccessfulPost(): void
    {
        $response = $this->handler()->handle(
            new ServerRequest()->withAttribute(SetPasswordHandler::class, ['success' => true]),
        );

        static::assertInstanceOf(RedirectResponse::class, $response);
    }

    #[Test]
    public function redirectsWhenNothingWasAttached(): void
    {
        $response = $this->handler()->handle(new ServerRequest());

        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertSame('/user/login', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function rendersTheForm(): void
    {
        $token = bin2hex(random_bytes(8));

        $response = $this->handler()->handle(
            new ServerRequest()->withAttribute(SetPasswordHandler::class, ['token' => $token]),
        );

        static::assertInstanceOf(HtmlResponse::class, $response);
        static::assertSame(200, $response->getStatusCode());
        static::assertSame('user::set-password', (string) $response->getBody());
    }

    #[Test]
    public function rendersTokenErrors(): void
    {
        $response = $this->handler()->handle(
            new ServerRequest()->withAttribute(SetPasswordHandler::class, [
                'error'   => 'Invalid verification link.',
                'expired' => false,
            ]),
        );

        static::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function rendersValidationErrorsWith422(): void
    {
        $response = $this->handler()->handle(
            new ServerRequest()->withAttribute(SetPasswordHandler::class, [
                'errors' => ['passwordHash' => ['Too short.']],
                'status' => 422,
            ]),
        );

        static::assertSame(422, $response->getStatusCode());
    }

    private function handler(): SetPasswordHandler
    {
        $template = $this->createStub(TemplateRendererInterface::class);
        $template->method('render')
            ->willReturnCallback(
                static fn(string $name): string => $name,
            );

        return new SetPasswordHandler(
            template: $template,
            loginUrl: '/user/login',
        );
    }
}
