<?php

declare(strict_types=1);

namespace Webware\UserManager;

use Laminas\InputFilter\InputFilterFactory;
use Webware\Admin\Container\Configuration as AdminConfiguration;
use Webware\Admin\Event\RegisterWidgetEvent;
use Webware\Console\ConsoleInterface;
use Webware\Core\AclInterface;
use Webware\Core\Role;
use Webware\Core\UserInterface;
use Webware\MessageBus\ConfigProvider as BusProvider;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Admin\Dashboard\Container\RegisterWidgetListenerFactory;
use Webware\UserManager\Admin\Dashboard\RegisterWidgetListener;
use Webware\UserManager\Console\Container\InitDbCommandFactory;
use Webware\UserManager\Console\InitDbCommand;
use Webware\UserManager\Repository\UserRepositoryInterface;
use Webware\UserManager\View\Helper\UserAdminUrl;
use Webware\UserManager\View\Helper\UserAdminUrlFactory;
use Webware\UserManager\View\Helper\UserUrl;
use Webware\UserManager\View\Helper\UserUrlFactory;

use function rtrim;

/**
 * @type AclConfig = array{
 *     roles: array<string, list<string>>,
 *     resources: array<string, bool>,
 *     rule_seed_providers: list<class-string>
 * }
 * @type AuthenticationConfig = array<string, non-empty-string>
 * @type DefaultConfig = array<string, non-empty-string>
 * @type Dependencies = array{
 *     aliases: array<interface-string, class-string>,
 *     factories: array<class-string, class-string>,
 *     invokables: array<class-string, class-string>
 * }
 * @type InputFilterConfig = array{factories: array<class-string, class-string>}
 * @type ListenerConfig = array<class-string, list<array{listener: class-string, priority: int}>>
 * @type RouteProviderConfig = array{route-providers: list<class-string>}
 * @type TemplateConfig = array{paths: array<string, list<string>>}
 * @type ViewHelperConfig = array{
 *     aliases: array<string, class-string>,
 *     factories: array<class-string, class-string>
 * }
 * @type MessageBusConfig = array{
 *     command_map: array<class-string, class-string>,
 *     query_map: array<class-string, class-string>
 * }
 * @type ProviderConfig = array{
 *     dependencies: Dependencies,
 *     input_filters: InputFilterConfig,
 *     router: RouteProviderConfig,
 *     templates: TemplateConfig,
 *     view_helpers: ViewHelperConfig,
 *     authentication: AuthenticationConfig,
 *     Webware\MessageBus\MessageBusInterface: MessageBusConfig,
 *     listeners: ListenerConfig,
 *     Webware\Core\UserInterface: DefaultConfig,
 *     Webware\Core\AclInterface: AclConfig,
 *     Webware\Console\ConsoleInterface: array{commands: array<string, class-string>}
 * }
 */
// @mago-expect lint:too-many-methods - accepted: one getter per config section, which keeps each section independently testable.
final class ConfigProvider
{
    /**
     * @return AclConfig
     */
    public function getAclConfig(): array
    {
        $public = Container\Configuration::getRouteNamePrefix();
        // Static default: the runtime admin name is resolved from config in the factories.
        $admin = Container\Configuration::getAdminRouteNamePrefix(AdminConfiguration::ADMIN_NAME);

        return [
            'roles'               => [
                Role::Guest->value  => [],
                Role::Member->value => [Role::Guest->value],
            ],
            'resources'           => [
                "{$public}session.read"               => true,
                "{$public}session.create"             => true,
                "{$public}register.read"              => true,
                "{$public}register.create"            => true,
                "{$public}verify.email.read"          => true,
                "{$public}resend.verification.read"   => true,
                "{$public}resend.verification.create" => true,
                "{$public}logout.read"                => true,
                "{$public}account.read"               => true,
                rtrim($admin, characters: '.')        => true,
                "{$admin}create"                      => true,
                "{$admin}update"                      => true,
                "{$admin}toggle.update"               => true,
            ],
            'rule_seed_providers' => [
                Acl\RuleSeeds::class,
            ],
        ];
    }

    /**
     * @return AuthenticationConfig
     */
    public function getAuthenticationConfig(): array
    {
        return [
            'redirect'                                       => '/'
                . Container\Configuration::getRouteSegment()
                . '/login',
            'username'                                       => 'email',
            'password'                                       => 'password',
            Container\Configuration::POST_LOGIN_REDIRECT_KEY => Container\Configuration::POST_LOGIN_REDIRECT_VALUE,
        ];
    }

    /** @return array<class-string, class-string> */
    public function getCommandMap(): array
    {
        return [
            Command\ActivateUserCommand::class                => CommandHandler\ActivateUserHandler::class,
            Command\CreateUserCommand::class                  => CommandHandler\CreateUserHandler::class,
            Command\RegenerateVerificationTokenCommand::class => CommandHandler\RegenerateVerificationTokenHandler::class,
            Command\ResendVerificationEmailCommand::class     => CommandHandler\ResendVerificationEmailHandler::class,
            Command\SendVerificationEmailCommand::class       => CommandHandler\SendVerificationEmailHandler::class,
            Command\ToggleUserActiveCommand::class            => CommandHandler\ToggleUserActiveHandler::class,
            Command\UpdateUserCommand::class                  => CommandHandler\UpdateUserHandler::class,
        ];
    }

    /**
     * @return DefaultConfig
     */
    public function getDefaultConfig(): array
    {
        return [
            'login_path' =>
                '/'
                    . Container\Configuration::getRouteSegment()
                    . '/login',
        ];
    }

    /**
     * @return Dependencies
     */
    public function getDependencies(): array
    {
        return [
            'aliases'    => [
                UserRepositoryInterface::class => Repository\UserRepository::class,
            ],
            'factories'  => [
                // Registers the user factory under our own interface key.
                UserInterface::class                                           => Container\UserFactory::class,
                Http\Admin\RequestHandler\CreateUserHandler::class             => Http\Admin\RequestHandler\Container\CreateUserHandlerFactory::class,
                Http\Admin\RequestHandler\ToggleUserActiveHandler::class       => Http\Admin\RequestHandler\Container\ToggleUserActiveHandlerFactory::class,
                Http\Admin\RequestHandler\UpdateUserHandler::class             => Http\Admin\RequestHandler\Container\UpdateUserHandlerFactory::class,
                Http\Admin\RequestHandler\UpdateUserModalHandler::class        => Http\Admin\RequestHandler\Container\UpdateUserModalHandlerFactory::class,
                CommandHandler\CreateUserHandler::class                        => CommandHandler\Container\CreateUserHandlerFactory::class,
                CommandHandler\ToggleUserActiveHandler::class                  => CommandHandler\Container\ToggleUserActiveHandlerFactory::class,
                CommandHandler\UpdateUserHandler::class                        => CommandHandler\Container\UpdateUserHandlerFactory::class,
                CommandHandler\ActivateUserHandler::class                      => CommandHandler\Container\ActivateUserHandlerFactory::class,
                CommandHandler\RegenerateVerificationTokenHandler::class       => CommandHandler\Container\RegenerateVerificationTokenHandlerFactory::class,
                CommandHandler\ResendVerificationEmailHandler::class           => CommandHandler\Container\ResendVerificationEmailHandlerFactory::class,
                CommandHandler\SendVerificationEmailHandler::class             => CommandHandler\Container\SendVerificationEmailHandlerFactory::class,
                QueryHandler\AuthenticateUserHandler::class                    => QueryHandler\Container\AuthenticateUserHandlerFactory::class,
                QueryHandler\CheckUserActiveHandler::class                     => QueryHandler\Container\CheckUserActiveHandlerFactory::class,
                QueryHandler\FetchUserByEmailHandler::class                    => QueryHandler\Container\FetchUserByEmailHandlerFactory::class,
                QueryHandler\FetchUserByIdHandler::class                       => QueryHandler\Container\FetchUserByIdHandlerFactory::class,
                QueryHandler\FetchUserByVerificationTokenHandler::class        => QueryHandler\Container\FetchUserByVerificationTokenHandlerFactory::class,
                QueryHandler\FetchUsersHandler::class                          => QueryHandler\Container\FetchUsersHandlerFactory::class,
                Http\Middleware\IdentityMiddleware::class                      => Http\Middleware\Container\IdentityMiddlewareFactory::class,
                Http\Middleware\LoginMiddleware::class                         => Http\Middleware\Container\LoginMiddlewareFactory::class,
                Http\Middleware\ProcessVerifyEmailMiddleware::class            => Http\Middleware\Container\ProcessVerifyEmailMiddlewareFactory::class,
                Http\Middleware\ProcessResendVerificationMiddleware::class     => Http\Middleware\Container\ProcessResendVerificationMiddlewareFactory::class,
                Http\Admin\Middleware\ProcessToggleUserActiveMiddleware::class => Http\Admin\Middleware\Container\ProcessToggleUserActiveMiddlewareFactory::class,
                Http\Admin\Middleware\ProcessUpdateUserMiddleware::class       => Http\Admin\Middleware\Container\ProcessUpdateUserMiddlewareFactory::class,
                Http\Middleware\RegistrationMiddleware::class                  => Http\Middleware\Container\RegistrationMiddlewareFactory::class,
                Repository\UserRepository::class                               => Repository\UserRepositoryFactory::class,
                InitDbCommand::class                                           => InitDbCommandFactory::class,
                RouteProvider::class                                           => Container\RouteProviderFactory::class,
                Http\RequestHandler\LoginHandler::class                        => Http\RequestHandler\Container\LoginHandlerFactory::class,
                Http\RequestHandler\LogoutHandler::class                       => Http\RequestHandler\Container\LogoutHandlerFactory::class,
                Http\RequestHandler\RegistrationHandler::class                 => Http\RequestHandler\Container\RegistrationHandlerFactory::class,
                Http\RequestHandler\ResendVerificationHandler::class           => Http\RequestHandler\Container\ResendVerificationHandlerFactory::class,
                Http\RequestHandler\UserListHandler::class                     => Http\RequestHandler\Container\UserListHandlerFactory::class,
                Http\RequestHandler\VerifyEmailHandler::class                  => Http\RequestHandler\Container\VerifyEmailHandlerFactory::class,
                Listener\SendVerificationEmailListener::class                  => Listener\Container\SendVerificationEmailListenerFactory::class,
                RegisterWidgetListener::class                                  => RegisterWidgetListenerFactory::class,
            ],
            'invokables' => [
                Entity\User::class   => Entity\User::class,
                Acl\RuleSeeds::class => Acl\RuleSeeds::class,
            ],
        ];
    }

    /**
     * @return InputFilterConfig
     */
    public function getInputFilterConfig(): array
    {
        return [
            'factories' => [
                InputFilter\UpdateUserDataFilter::class   => InputFilterFactory::class,
                InputFilter\RegistrationDataFilter::class => InputFilterFactory::class,
            ],
        ];
    }

    /**
     * @return ListenerConfig
     */
    public function getListeners(): array
    {
        return [
            RegisterWidgetEvent::class => [
                ['listener' => RegisterWidgetListener::class, 'priority' => 1],
            ],
        ];
    }

    /** @return array<class-string, class-string> */
    public function getQueryMap(): array
    {
        return [
            Query\AuthenticateUserQuery::class             => QueryHandler\AuthenticateUserHandler::class,
            Query\CheckUserActiveQuery::class              => QueryHandler\CheckUserActiveHandler::class,
            Query\FetchUserByEmailQuery::class             => QueryHandler\FetchUserByEmailHandler::class,
            Query\FetchUserByIdQuery::class                => QueryHandler\FetchUserByIdHandler::class,
            Query\FetchUserByVerificationTokenQuery::class => QueryHandler\FetchUserByVerificationTokenHandler::class,
            Query\FetchUsersQuery::class                   => QueryHandler\FetchUsersHandler::class,
        ];
    }

    /**
     * @return RouteProviderConfig
     */
    public function getRouteProviders(): array
    {
        return [
            'route-providers' => [
                RouteProvider::class,
            ],
        ];
    }

    /**
     * @return TemplateConfig
     */
    public function getTemplates(): array
    {
        return [
            'paths' => [
                'user' => [__DIR__ . '/../templates/default/user'],
            ],
        ];
    }

    /**
     * @return ViewHelperConfig
     */
    public function getViewHelpers(): array
    {
        return [
            'aliases'   => [
                'userUrl'      => UserUrl::class,
                'userAdminUrl' => UserAdminUrl::class,
            ],
            'factories' => [
                UserUrl::class      => UserUrlFactory::class,
                UserAdminUrl::class => UserAdminUrlFactory::class,
            ],
        ];
    }

    /**
     * @return ProviderConfig
     */
    public function __invoke(): array
    {
        return [
            'dependencies'             => $this->getDependencies(),
            'input_filters'            => $this->getInputFilterConfig(),
            'router'                   => $this->getRouteProviders(),
            'templates'                => $this->getTemplates(),
            'view_helpers'             => $this->getViewHelpers(),
            'authentication'           => $this->getAuthenticationConfig(),
            MessageBusInterface::class => [
                BusProvider::COMMAND_MAP_KEY => $this->getCommandMap(),
                BusProvider::QUERY_MAP_KEY   => $this->getQueryMap(),
            ],
            'listeners'                => $this->getListeners(),
            UserInterface::class       => $this->getDefaultConfig(),
            AclInterface::class        => $this->getAclConfig(),
            ConsoleInterface::class    => [
                'commands' => [
                    'user:init-db' => InitDbCommand::class,
                ],
            ],
        ];
    }
}
