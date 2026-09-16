<?php

declare(strict_types=1);

namespace Webware\UserManager\View\Helper;

use InvalidArgumentException;
use Laminas\View\Helper\StatefulHelperInterface;
use Mezzio\Helper\Exception\ExceptionInterface as HelperException;
use Mezzio\Helper\UrlHelper;
use Override;

use function rtrim;

final readonly class UserAdminUrl implements StatefulHelperInterface
{
    public function __construct(
        private UrlHelper $urlHelper,
        private string $routeNamePrefix,
    ) {}

    #[Override]
    public function resetState(): void {}

    /**
     * @param array<string, mixed> $routeParams
     * @param array<string, mixed> $queryParams
     * @param array{router?: array<array-key, mixed>, reuse_result_params?: bool, reuse_query_params?: bool} $options
     * @throws HelperException
     * @throws InvalidArgumentException
     */
    public function __invoke(
        string $routeName,
        array $routeParams = [],
        array $queryParams = [],
        ?string $fragmentIdentifier = null,
        array $options = [],
    ): string {
        $prefix = '' === $routeName
            ? rtrim(
                string    : $this->routeNamePrefix,
                characters: '.',
            ) : $this->routeNamePrefix;

        return ($this->urlHelper)(
            $prefix . $routeName,
            $routeParams,
            $queryParams,
            $fragmentIdentifier,
            $options,
        );
    }
}
