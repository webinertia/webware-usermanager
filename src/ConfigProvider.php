<?php

declare(strict_types=1);

namespace Webware\UserManager;

use Laminas\InputFilter\InputFilterFactory;
use Webware\Admin\Container\Configuration as AdminConfiguration;
use Webware\Admin\Event\RegisterWidgetEvent;
use Webware\Console\ConsoleInterface;
use Webware\Core\AclInterface;
use Webware\Core\UserInterface;
use Webware\MessageBus\ConfigProvider as BusProvider;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Admin\Dashboard\Container\RegisterWidgetListenerFactory;
use Webware\UserManager\Admin\Dashboard\RegisterWidgetListener;
use Webware\UserManager\Console\Container\InitDbCommandFactory;
use Webware\UserManager\Console\InitDbCommand;
use Webware\UserManager\Query\AuthenticateUserQuery;
use Webware\UserManager\Query\CheckUserActiveQuery;
use Webware\UserManager\Query\FetchUserByEmailQuery;
use Webware\UserManager\Query\FetchUserByIdQuery;
use Webware\UserManager\Query\FetchUserByVerificationTokenQuery;
use Webware\UserManager\Query\FetchUsersQuery;
use Webware\UserManager\QueryHandler\AuthenticateUserHandler;
use Webware\UserManager\QueryHandler\CheckUserActiveHandler;
use Webware\UserManager\QueryHandler\Container\AuthenticateUserHandlerFactory;
use Webware\UserManager\QueryHandler\Container\CheckUserActiveHandlerFactory;
use Webware\UserManager\QueryHandler\Container\FetchUserByEmailHandlerFactory;
use Webware\UserManager\QueryHandler\Container\FetchUserByIdHandlerFactory;
use Webware\UserManager\QueryHandler\Container\FetchUserByVerificationTokenHandlerFactory;
use Webware\UserManager\QueryHandler\Container\FetchUsersHandlerFactory;
use Webware\UserManager\QueryHandler\FetchUserByEmailHandler;
use Webware\UserManager\QueryHandler\FetchUserByIdHandler;
use Webware\UserManager\QueryHandler\FetchUserByVerificationTokenHandler;
use Webware\UserManager\QueryHandler\FetchUsersHandler;
use Webware\UserManager\Repository\UserRepositoryInterface;
use Webware\UserManager\View\Helper\UserAdminUrl;
use Webware\UserManager\View\Helper\UserAdminUrlFactory;
use Webware\UserManager\View\Helper\UserUrl;
use Webware\UserManager\View\Helper\UserUrlFactory;

use function rtrim;

final class ConfigProvider
{
    public function getAclConfig(): array
    {
        return [
            'roles'     => [
                'Guest'  => [],
                'Member' => ['Guest'],
            ],
            'resources' => [
                Container\Configuration::ROUTE_NAME_PREFIX_VALUE
                    . 'session.read' => true,
                Container\Configuration::ROUTE_NAME_PREFIX_VALUE
                    . 'session.create' => true,
                Container\Configuration::ROUTE_NAME_PREFIX_VALUE
                    . 'register.read' => true,
                Container\Configuration::ROUTE_NAME_PREFIX_VALUE
                    . 'register.create' => true,
                Container\Configuration::ROUTE_NAME_PREFIX_VALUE
                    . 'verify.email.read' => true,
                Container\Configuration::ROUTE_NAME_PREFIX_VALUE
                    . 'resend.verification.read' => true,
                Container\Configuration::ROUTE_NAME_PREFIX_VALUE
                    . 'resend.verification.create' => true,
                Container\Configuration::ROUTE_NAME_PREFIX_VALUE
                    . 'logout.read' => true,
                Container\Configuration::ROUTE_NAME_PREFIX_VALUE
                    . 'account.read' => true,
                AdminConfiguration::ADMIN_ROUTE_NAME_PREFIX_VALUE
                    . rtrim(
                        Container\Configuration::ADMIN_ROUTE_NAME_PREFIX_VALUE,
                        '.',
                    ) => true,
                AdminConfiguration::ADMIN_ROUTE_NAME_PREFIX_VALUE
                    . Container\Configuration::ADMIN_ROUTE_NAME_PREFIX_VALUE
                    . 'create' => true,
                AdminConfiguration::ADMIN_ROUTE_NAME_PREFIX_VALUE
                    . Container\Configuration::ADMIN_ROUTE_NAME_PREFIX_VALUE
                    . 'update' => true,
                AdminConfiguration::ADMIN_ROUTE_NAME_PREFIX_VALUE
                    . Container\Configuration::ADMIN_ROUTE_NAME_PREFIX_VALUE
                    . 'toggle.update' => true,
            ],
            'allow'     => [
                'Guest'         => [
                    Container\Configuration::ROUTE_NAME_PREFIX_VALUE . 'session.read'               => [],
                    Container\Configuration::ROUTE_NAME_PREFIX_VALUE . 'session.create'             => [],
                    Container\Configuration::ROUTE_NAME_PREFIX_VALUE . 'register.read'              => [],
                    Container\Configuration::ROUTE_NAME_PREFIX_VALUE . 'register.create'            => [],
                    Container\Configuration::ROUTE_NAME_PREFIX_VALUE . 'verify.email.read'          => [],
                    Container\Configuration::ROUTE_NAME_PREFIX_VALUE . 'resend.verification.read'   => [],
                    Container\Configuration::ROUTE_NAME_PREFIX_VALUE . 'resend.verification.create' => [],
                ],
                'Member'        => [
                    Container\Configuration::ROUTE_NAME_PREFIX_VALUE . 'logout.read' => [],
                ],
                'Administrator' => [
                    AdminConfiguration::ADMIN_ROUTE_NAME_PREFIX_VALUE
                        . rtrim(
                            Container\Configuration::ADMIN_ROUTE_NAME_PREFIX_VALUE,
                            '.',
                        ) => [],
                    AdminConfiguration::ADMIN_ROUTE_NAME_PREFIX_VALUE
                        . Container\Configuration::ADMIN_ROUTE_NAME_PREFIX_VALUE
                        . 'create'                                                             => [],
                    AdminConfiguration::ADMIN_ROUTE_NAME_PREFIX_VALUE
                        . Container\Configuration::ADMIN_ROUTE_NAME_PREFIX_VALUE
                        . 'update'                                                             => [],
                    AdminConfiguration::ADMIN_ROUTE_NAME_PREFIX_VALUE
                        . Container\Configuration::ADMIN_ROUTE_NAME_PREFIX_VALUE
                        . 'toggle.update'                                                      => [],
                ],
            ],
            'deny'      => [
                'Member' => [
                    Container\Configuration::ROUTE_NAME_PREFIX_VALUE . 'session.read'               => [],
                    Container\Configuration::ROUTE_NAME_PREFIX_VALUE . 'session.create'             => [],
                    Container\Configuration::ROUTE_NAME_PREFIX_VALUE . 'register.read'              => [],
                    Container\Configuration::ROUTE_NAME_PREFIX_VALUE . 'register.create'            => [],
                    Container\Configuration::ROUTE_NAME_PREFIX_VALUE . 'verify.email.read'          => [],
                    Container\Configuration::ROUTE_NAME_PREFIX_VALUE . 'resend.verification.read'   => [],
                    Container\Configuration::ROUTE_NAME_PREFIX_VALUE . 'resend.verification.create' => [],
                ],
            ],
        ];
    }

    public function getAuthenticationConfig(): array
    {
        return [
            'redirect'                                       => '/'
                . Container\Configuration::ROUTE_SEGMENT_VALUE
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
            Command\ToggleUserActiveCommand::class            => CommandHandler\ToggleUserActiveHandler::class,
            Command\UpdateUserCommand::class                  => CommandHandler\UpdateUserHandler::class,
        ];
    }

    public function getDefaultConfig(): array
    {
        return [
            Container\Configuration::ROUTE_SEGMENT_KEY           => Container\Configuration::ROUTE_SEGMENT_VALUE,
            Container\Configuration::ROUTE_NAME_PREFIX_KEY       => Container\Configuration::ROUTE_NAME_PREFIX_VALUE,
            Container\Configuration::ADMIN_ROUTE_SEGMENT_KEY     => Container\Configuration::ADMIN_ROUTE_SEGMENT_VALUE,
            Container\Configuration::ADMIN_ROUTE_NAME_PREFIX_KEY => Container\Configuration::ADMIN_ROUTE_NAME_PREFIX_VALUE,
            'login_path'                                         => '/'
                . Container\Configuration::ROUTE_SEGMENT_VALUE
                . '/login',
        ];
    }

    public function getDependencies(): array
    {
        return [
            'aliases'    => [
                UserRepositoryInterface::class => Repository\UserRepository::class,
            ],
            'factories'  => [
                // Registers the user factory under our own interface key.
                UserInterface::class                                           => Container\UserFactory::class,
                Entity\User::class                                             => Entity\User::class,
                Http\Admin\RequestHandler\CreateUserHandler::class             => Http\Admin\RequestHandler\Container\CreateUserHandlerFactory::class,
                Http\Admin\RequestHandler\ToggleUserActiveHandler::class       => Http\Admin\RequestHandler\Container\ToggleUserActiveHandlerFactory::class,
                Http\Admin\RequestHandler\UpdateUserHandler::class             => Http\Admin\RequestHandler\Container\UpdateUserHandlerFactory::class,
                Http\Admin\RequestHandler\UpdateUserModalHandler::class        => Http\Admin\RequestHandler\Container\UpdateUserModalHandlerFactory::class,
                CommandHandler\CreateUserHandler::class                        => CommandHandler\Container\CreateUserHandlerFactory::class,
                CommandHandler\ToggleUserActiveHandler::class                  => CommandHandler\Container\ToggleUserActiveHandlerFactory::class,
                CommandHandler\UpdateUserHandler::class                        => CommandHandler\Container\UpdateUserHandlerFactory::class,
                CommandHandler\ActivateUserHandler::class                      => CommandHandler\Container\ActivateUserHandlerFactory::class,
                CommandHandler\RegenerateVerificationTokenHandler::class       => CommandHandler\Container\RegenerateVerificationTokenHandlerFactory::class,
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
                Entity\User::class => Entity\User::class,
            ],
        ];
    }

    public function getInputFilterConfig(): array
    {
        return [
            'factories' => [
                InputFilter\UpdateUserDataFilter::class   => InputFilterFactory::class,
                InputFilter\RegistrationDataFilter::class => InputFilterFactory::class,
            ],
        ];
    }

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

    public function getRouteProviders(): array
    {
        return [
            'route-providers' => [
                RouteProvider::class,
            ],
        ];
    }

    public function getTemplates(): array
    {
        return [
            'paths' => [
                'user' => [__DIR__ . '/../templates/user'],
            ],
        ];
    }

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
