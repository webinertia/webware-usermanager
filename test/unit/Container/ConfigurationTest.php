<?php

declare(strict_types=1);

namespace WebwareTest\UserManager\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Core\Exception\ContainerException;
use Webware\Core\UserInterface;
use Webware\UserManager\Container\Configuration;

#[CoversClass(Configuration::class)]
#[CoversMethod(Configuration::class, 'getBaseUrl')]
#[CoversMethod(Configuration::class, 'getPostLoginRedirect')]
#[CoversMethod(Configuration::class, 'getVerificationEmailSubject')]
#[CoversMethod(Configuration::class, 'getVerificationTokenTtl')]
final class ConfigurationTest extends TestCase
{
    #[Test]
    public function exposesConfigConstants(): void
    {
        self::assertSame(UserInterface::class, Configuration::CONFIG_KEY);
        self::assertSame('user.manager', Configuration::ROUTE_SEGMENT_VALUE);
        self::assertSame('user.manager.', Configuration::ROUTE_NAME_PREFIX_VALUE);
    }

    #[Test]
    public function fallsBackToDefaultWhenPostLoginRedirectIsEmpty(): void
    {
        self::assertSame(
            Configuration::POST_LOGIN_REDIRECT_VALUE,
            Configuration::getPostLoginRedirect(
                $this->containerWith(['authentication' => ['post_login_redirect' => '']]),
                'TestFactory',
            ),
        );
    }

    #[Test]
    public function fallsBackToDefaultWhenPostLoginRedirectIsMissing(): void
    {
        self::assertSame(
            Configuration::POST_LOGIN_REDIRECT_VALUE,
            Configuration::getPostLoginRedirect(
                $this->containerWith(['authentication' => ['username' => 'email']]),
                'TestFactory',
            ),
        );
    }

    #[Test]
    public function fallsBackToDefaultWhenPostLoginRedirectIsNotAString(): void
    {
        self::assertSame(
            Configuration::POST_LOGIN_REDIRECT_VALUE,
            Configuration::getPostLoginRedirect(
                $this->containerWith(['authentication' => ['post_login_redirect' => 42]]),
                'TestFactory',
            ),
        );
    }

    #[Test]
    public function returnsAuthenticationConfigWhenPresent(): void
    {
        self::assertSame(
            ['username' => 'email'],
            Configuration::getCredentialConfig($this->containerWith(['authentication' => [
                'username' => 'email',
            ]]), 'TestFactory'),
        );
    }

    #[Test]
    public function returnsBaseUrlWhenPresent(): void
    {
        self::assertSame(
            'https://example.com',
            Configuration::getBaseUrl(
                $this->containerWith([UserInterface::class => ['base_url' => 'https://example.com']]),
                'TestFactory',
            ),
        );
    }

    #[Test]
    public function returnsPostLoginRedirectWhenPresent(): void
    {
        self::assertSame(
            '/dashboard',
            Configuration::getPostLoginRedirect(
                $this->containerWith(['authentication' => ['post_login_redirect' => '/dashboard']]),
                'TestFactory',
            ),
        );
    }

    #[Test]
    public function returnsVerificationEmailSubjectWhenPresent(): void
    {
        self::assertSame(
            'Verify your email',
            Configuration::getVerificationEmailSubject(
                $this->containerWith([UserInterface::class => ['verification_email_subject' => 'Verify your email']]),
                'TestFactory',
            ),
        );
    }

    #[Test]
    public function returnsVerificationTokenTtlWhenItIsOne(): void
    {
        self::assertSame(
            1,
            Configuration::getVerificationTokenTtl(
                $this->containerWith([UserInterface::class => ['verification_token_ttl' => 1]]),
                'TestFactory',
            ),
        );
    }

    #[Test]
    public function returnsVerificationTokenTtlWhenPresent(): void
    {
        self::assertSame(
            3600,
            Configuration::getVerificationTokenTtl(
                $this->containerWith([UserInterface::class => ['verification_token_ttl' => 3600]]),
                'TestFactory',
            ),
        );
    }

    #[Test]
    public function throwsWhenAuthenticationConfigEmpty(): void
    {
        $this->expectException(ContainerException::class);

        Configuration::getCredentialConfig($this->containerWith(['authentication' => []]), 'TestFactory');
    }

    #[Test]
    public function throwsWhenAuthenticationConfigNotArray(): void
    {
        $this->expectException(ContainerException::class);

        Configuration::getCredentialConfig($this->containerWith(['authentication' => 'nope']), 'TestFactory');
    }

    #[Test]
    public function throwsWhenAuthenticationKeyMissing(): void
    {
        $this->expectException(ContainerException::class);

        Configuration::getCredentialConfig($this->containerWith([]), 'TestFactory');
    }

    #[Test]
    public function throwsWhenBaseUrlIsEmpty(): void
    {
        $this->expectException(ContainerException::class);

        Configuration::getBaseUrl(
            $this->containerWith([UserInterface::class => ['base_url' => '']]),
            'TestFactory',
        );
    }

    #[Test]
    public function throwsWhenBaseUrlIsNotAString(): void
    {
        $this->expectException(ContainerException::class);

        Configuration::getBaseUrl(
            $this->containerWith([UserInterface::class => ['base_url' => 8080]]),
            'TestFactory',
        );
    }

    #[Test]
    public function throwsWhenBaseUrlMissing(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessageMatches('/base_url/');

        Configuration::getBaseUrl(
            $this->containerWith([UserInterface::class => ['verification_email_subject' => 'Verify']]),
            'TestFactory',
        );
    }

    #[Test]
    public function throwsWhenComponentSectionIsEmpty(): void
    {
        $this->expectException(ContainerException::class);

        Configuration::getBaseUrl($this->containerWith([UserInterface::class => []]), 'TestFactory');
    }

    #[Test]
    public function throwsWhenComponentSectionMissing(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessageMatches('/UserInterface/');

        Configuration::getBaseUrl($this->containerWith([]), 'TestFactory');
    }

    #[Test]
    public function throwsWhenConfigServiceIsMissing(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            'The "config" service was not found in the container. Requested by factory: TestFactory',
        );

        Configuration::getBaseUrl($container, 'TestFactory');
    }

    #[Test]
    public function throwsWhenVerificationEmailSubjectMissing(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessageMatches('/verification_email_subject/');

        Configuration::getVerificationEmailSubject(
            $this->containerWith([UserInterface::class => ['base_url' => 'https://example.com']]),
            'TestFactory',
        );
    }

    #[Test]
    public function throwsWhenVerificationTokenTtlIsNotAnInt(): void
    {
        $this->expectException(ContainerException::class);

        Configuration::getVerificationTokenTtl(
            $this->containerWith([UserInterface::class => ['verification_token_ttl' => '3600']]),
            'TestFactory',
        );
    }

    #[Test]
    public function throwsWhenVerificationTokenTtlIsNotPositive(): void
    {
        $this->expectException(ContainerException::class);

        Configuration::getVerificationTokenTtl(
            $this->containerWith([UserInterface::class => ['verification_token_ttl' => 0]]),
            'TestFactory',
        );
    }

    #[Test]
    public function throwsWhenVerificationTokenTtlMissing(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessageMatches('/verification_token_ttl/');

        Configuration::getVerificationTokenTtl(
            $this->containerWith([UserInterface::class => ['base_url' => 'https://example.com']]),
            'TestFactory',
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private function containerWith(array $config): ContainerInterface
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturnMap([['config', $config]]);

        return $container;
    }
}
