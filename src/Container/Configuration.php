<?php

declare(strict_types=1);

namespace Webware\UserManager\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Core\Configuration as Config;
use Webware\Core\Exception;
use Webware\Core\UserInterface;

use function array_key_exists;
use function get_debug_type;
use function is_array;
use function is_int;
use function is_string;

final readonly class Configuration extends Config
{
    public const string CONFIG_KEY = UserInterface::class;

    public const string ROUTE_SEGMENT_VALUE = 'user.manager';

    public const string ROUTE_NAME_PREFIX_VALUE = 'user.manager.';

    public const string ADMIN_ROUTE_SEGMENT_VALUE = 'user.manager';

    public const string ADMIN_ROUTE_NAME_PREFIX_VALUE = 'user.manager.';

    public const string POST_LOGIN_REDIRECT_KEY = 'post_login_redirect';

    public const string POST_LOGIN_REDIRECT_VALUE = '/';

    public const string BASE_URL_KEY = 'base_url';

    public const string VERIFICATION_EMAIL_SUBJECT_KEY = 'verification_email_subject';

    public const string VERIFICATION_TOKEN_TTL_KEY = 'verification_token_ttl';

    private const string MEZZIO_AUTH_KEY = 'authentication';

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception\ExceptionInterface
     */
    public static function getBaseUrl(ContainerInterface $container, string $callingFactory): string
    {
        return self::requireNonEmptyString($container, self::BASE_URL_KEY, $callingFactory);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception\ExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getCredentialConfig(
        ContainerInterface $container,
        string $callingFactory,
    ): array {
        $config = $container->get('config');

        if (! isset($config[self::MEZZIO_AUTH_KEY])) {
            throw Exception\ContainerException::forMissingConfigKey(self::MEZZIO_AUTH_KEY, $callingFactory);
        }

        if (! is_array($config[self::MEZZIO_AUTH_KEY]) || [] === $config[self::MEZZIO_AUTH_KEY]) {
            throw Exception\ContainerException::forInvalidConfigType(
                self::MEZZIO_AUTH_KEY,
                'array',
                get_debug_type(
                    $config[self::MEZZIO_AUTH_KEY],
                ),
                $callingFactory,
            );
        }

        return $config[self::MEZZIO_AUTH_KEY];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception\ExceptionInterface
     */
    public static function getVerificationEmailSubject(ContainerInterface $container, string $callingFactory): string
    {
        return self::requireNonEmptyString($container, self::VERIFICATION_EMAIL_SUBJECT_KEY, $callingFactory);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception\ExceptionInterface
     */
    public static function getVerificationTokenTtl(ContainerInterface $container, string $callingFactory): int
    {
        $key = self::VERIFICATION_TOKEN_TTL_KEY;

        /** @var mixed $value */
        $value = self::read($container, $key, $callingFactory);

        if (! is_int($value) || $value < 1) {
            throw Exception\ContainerException::forInvalidConfigType(
                $key,
                'positive-int',
                get_debug_type($value),
                $callingFactory,
            );
        }

        return $value;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception\ExceptionInterface
     * @return array<string, mixed>
     */
    private static function getSection(ContainerInterface $container, string $callingFactory): array
    {
        if (! $container->has('config')) {
            throw Exception\ContainerException::forMissingConfigService('config', $callingFactory);
        }

        /** @var mixed $config */
        $config = $container->get('config');

        /** @var mixed $section */
        $section = is_array($config) ? $config[self::CONFIG_KEY] ?? null : null;

        if (! is_array($section) || [] === $section) {
            throw Exception\ContainerException::forMissingConfigKey(self::CONFIG_KEY, $callingFactory);
        }

        /** @var array<string, mixed> $section */
        return $section;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception\ExceptionInterface
     */
    private static function read(ContainerInterface $container, string $key, string $callingFactory): mixed
    {
        $section = self::getSection($container, $callingFactory);

        if (! array_key_exists($key, $section)) {
            throw Exception\ContainerException::forMissingConfigKey($key, $callingFactory);
        }

        return $section[$key];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception\ExceptionInterface
     */
    private static function requireNonEmptyString(
        ContainerInterface $container,
        string $key,
        string $callingFactory,
    ): string {
        /** @var mixed $value */
        $value = self::read($container, $key, $callingFactory);

        if (! is_string($value)) {
            throw Exception\ContainerException::forInvalidConfigType(
                $key,
                'string',
                get_debug_type($value),
                $callingFactory,
            );
        }

        if ('' === $value) {
            throw Exception\ContainerException::forEmptyConfiguration($key, $callingFactory);
        }

        return $value;
    }
}
