<?php

declare(strict_types=1);

namespace WebwareTest\UserManager;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Admin\Event\RegisterWidgetEvent;
use Webware\Console\ConsoleInterface;
use Webware\Core\AclInterface;
use Webware\Core\UserInterface;
use Webware\MessageBus\ConfigProvider as BusProvider;
use Webware\MessageBus\MessageBusInterface;
use Webware\UserManager\Admin\Dashboard\RegisterWidgetListener;
use Webware\UserManager\Command\ActivateUserCommand;
use Webware\UserManager\Command\CreateUserCommand;
use Webware\UserManager\Command\RegenerateVerificationTokenCommand;
use Webware\UserManager\Command\ToggleUserActiveCommand;
use Webware\UserManager\Command\UpdateUserCommand;
use Webware\UserManager\ConfigProvider;
use Webware\UserManager\Console\InitDbCommand;
use Webware\UserManager\Container\UserFactory;
use Webware\UserManager\Entity\User;
use Webware\UserManager\InputFilter\RegistrationDataFilter;
use Webware\UserManager\InputFilter\UpdateUserDataFilter;
use Webware\UserManager\Query\AuthenticateUserQuery;
use Webware\UserManager\Query\CheckUserActiveQuery;
use Webware\UserManager\Query\FetchUserByEmailQuery;
use Webware\UserManager\Query\FetchUserByIdQuery;
use Webware\UserManager\Query\FetchUserByVerificationTokenQuery;
use Webware\UserManager\Query\FetchUsersQuery;
use Webware\UserManager\QueryHandler\AuthenticateUserHandler;
use Webware\UserManager\QueryHandler\CheckUserActiveHandler;
use Webware\UserManager\QueryHandler\FetchUserByEmailHandler;
use Webware\UserManager\QueryHandler\FetchUserByIdHandler;
use Webware\UserManager\QueryHandler\FetchUserByVerificationTokenHandler;
use Webware\UserManager\QueryHandler\FetchUsersHandler;
use Webware\UserManager\Repository\UserRepository;
use Webware\UserManager\Repository\UserRepositoryInterface;
use Webware\UserManager\RouteProvider;
use Webware\UserManager\View\Helper\UserAdminUrl;
use Webware\UserManager\View\Helper\UserUrl;

use function dirname;

#[CoversClass(ConfigProvider::class)]
#[CoversMethod(ConfigProvider::class, 'getAclConfig')]
#[CoversMethod(ConfigProvider::class, 'getAuthenticationConfig')]
#[CoversMethod(ConfigProvider::class, 'getCommandMap')]
#[CoversMethod(ConfigProvider::class, 'getQueryMap')]
#[CoversMethod(ConfigProvider::class, 'getDefaultConfig')]
#[CoversMethod(ConfigProvider::class, 'getDependencies')]
#[CoversMethod(ConfigProvider::class, 'getInputFilterConfig')]
#[CoversMethod(ConfigProvider::class, 'getListeners')]
#[CoversMethod(ConfigProvider::class, 'getRouteProviders')]
#[CoversMethod(ConfigProvider::class, 'getTemplates')]
#[CoversMethod(ConfigProvider::class, 'getViewHelpers')]
#[CoversMethod(ConfigProvider::class, '__invoke')]
final class ConfigProviderTest extends TestCase
{
    private ConfigProvider $provider;

    #[Test]
    public function aclConfigExposesRolesResourcesAndRules(): void
    {
        self::assertSame(
            [
                'roles'     => [
                    'Guest'  => [],
                    'Member' => ['Guest'],
                ],
                'resources' => [
                    'user.manager.session.read'                => true,
                    'user.manager.session.create'              => true,
                    'user.manager.register.read'               => true,
                    'user.manager.register.create'             => true,
                    'user.manager.verify.email.read'           => true,
                    'user.manager.resend.verification.read'    => true,
                    'user.manager.resend.verification.create'  => true,
                    'user.manager.logout.read'                 => true,
                    'user.manager.account.read'                => true,
                    'webware.admin.user.manager'               => true,
                    'webware.admin.user.manager.create'        => true,
                    'webware.admin.user.manager.update'        => true,
                    'webware.admin.user.manager.toggle.update' => true,
                ],
                'allow'     => [
                    'Guest'         => [
                        'user.manager.session.read'               => [],
                        'user.manager.session.create'             => [],
                        'user.manager.register.read'              => [],
                        'user.manager.register.create'            => [],
                        'user.manager.verify.email.read'          => [],
                        'user.manager.resend.verification.read'   => [],
                        'user.manager.resend.verification.create' => [],
                    ],
                    'Member'        => [
                        'user.manager.logout.read' => [],
                    ],
                    'Administrator' => [
                        'webware.admin.user.manager'               => [],
                        'webware.admin.user.manager.create'        => [],
                        'webware.admin.user.manager.update'        => [],
                        'webware.admin.user.manager.toggle.update' => [],
                    ],
                ],
                'deny'      => [
                    'Member' => [
                        'user.manager.session.read'               => [],
                        'user.manager.session.create'             => [],
                        'user.manager.register.read'              => [],
                        'user.manager.register.create'            => [],
                        'user.manager.verify.email.read'          => [],
                        'user.manager.resend.verification.read'   => [],
                        'user.manager.resend.verification.create' => [],
                    ],
                ],
            ],
            $this->provider->getAclConfig(),
        );
    }

    #[Test]
    public function authenticationConfigUsesEmailCredentials(): void
    {
        $config = $this->provider->getAuthenticationConfig();

        self::assertSame('email', $config['username']);
        self::assertSame('password', $config['password']);
        self::assertSame('/user.manager/login', $config['redirect']);
        self::assertSame('/', $config['post_login_redirect']);
    }

    #[Test]
    public function commandMapMapsEveryCommandToAHandler(): void
    {
        $map = $this->provider->getCommandMap();

        self::assertCount(5, $map);
        self::assertArrayHasKey(ActivateUserCommand::class, $map);
        self::assertArrayHasKey(CreateUserCommand::class, $map);
        self::assertArrayHasKey(RegenerateVerificationTokenCommand::class, $map);
        self::assertArrayHasKey(ToggleUserActiveCommand::class, $map);
        self::assertArrayHasKey(UpdateUserCommand::class, $map);
    }

    #[Test]
    public function defaultConfigHoldsRouteAndLoginDefaults(): void
    {
        $config = $this->provider->getDefaultConfig();

        self::assertSame('user.manager', $config['route_segment']);
        self::assertSame('user.manager.', $config['route_name_prefix']);
        self::assertSame('user.manager', $config['admin_route_segment']);
        self::assertSame('user.manager.', $config['admin_route_name_prefix']);
        self::assertSame('/user.manager/login', $config['login_path']);
    }

    #[Test]
    public function dependenciesRegisterAliasesFactoriesAndInvokables(): void
    {
        $deps = $this->provider->getDependencies();

        self::assertSame(
            UserRepository::class,
            $deps['aliases'][UserRepositoryInterface::class],
        );
        self::assertSame(UserFactory::class, $deps['factories'][UserInterface::class]);
        self::assertSame(User::class, $deps['factories'][User::class]);
        self::assertArrayHasKey(User::class, $deps['invokables']);
        self::assertArrayHasKey(InitDbCommand::class, $deps['factories']);
    }

    #[Test]
    public function inputFilterConfigRegistersBothFilters(): void
    {
        $config = $this->provider->getInputFilterConfig();

        self::assertArrayHasKey(UpdateUserDataFilter::class, $config['factories']);
        self::assertArrayHasKey(RegistrationDataFilter::class, $config['factories']);
    }

    #[Test]
    public function invokeAggregatesAllTopLevelConfig(): void
    {
        $config = $this->provider->__invoke();

        self::assertArrayHasKey('dependencies', $config);
        self::assertArrayHasKey('input_filters', $config);
        self::assertArrayHasKey('router', $config);
        self::assertArrayHasKey('templates', $config);
        self::assertArrayHasKey('view_helpers', $config);
        self::assertArrayHasKey('authentication', $config);
        self::assertArrayHasKey(MessageBusInterface::class, $config);
        self::assertArrayHasKey('listeners', $config);
        self::assertArrayHasKey(UserInterface::class, $config);
        self::assertArrayHasKey(AclInterface::class, $config);
        self::assertSame(InitDbCommand::class, $config[ConsoleInterface::class]['commands']['user:init-db']);
        self::assertSame(
            $this->provider->getCommandMap(),
            $config[MessageBusInterface::class][BusProvider::COMMAND_MAP_KEY],
        );
        self::assertSame(
            $this->provider->getQueryMap(),
            $config[MessageBusInterface::class][BusProvider::QUERY_MAP_KEY],
        );
    }

    #[Test]
    public function listenersRegisterWidgetListener(): void
    {
        self::assertSame(
            [
                RegisterWidgetEvent::class => [
                    ['listener' => RegisterWidgetListener::class, 'priority' => 1],
                ],
            ],
            $this->provider->getListeners(),
        );
    }

    #[Test]
    public function queryMapMapsEveryQueryToAHandler(): void
    {
        self::assertSame(
            [
                AuthenticateUserQuery::class             => AuthenticateUserHandler::class,
                CheckUserActiveQuery::class              => CheckUserActiveHandler::class,
                FetchUserByEmailQuery::class             => FetchUserByEmailHandler::class,
                FetchUserByIdQuery::class                => FetchUserByIdHandler::class,
                FetchUserByVerificationTokenQuery::class => FetchUserByVerificationTokenHandler::class,
                FetchUsersQuery::class                   => FetchUsersHandler::class,
            ],
            $this->provider->getQueryMap(),
        );
    }

    #[Test]
    public function routeProvidersExposeRouteProvider(): void
    {
        $providers = $this->provider->getRouteProviders();

        self::assertSame([RouteProvider::class], $providers['route-providers']);
    }

    #[Test]
    public function templatesRegisterUserTemplatePath(): void
    {
        self::assertSame(
            [
                'paths' => [
                    'user' => [
                        dirname(
                            path  : __DIR__,
                            levels: 2,
                        ) . '/src/../templates/user',
                    ],
                ],
            ],
            $this->provider->getTemplates(),
        );
    }

    #[Test]
    public function viewHelpersRegisterAliasesAndFactories(): void
    {
        $helpers = $this->provider->getViewHelpers();

        self::assertSame(UserUrl::class, $helpers['aliases']['userUrl']);
        self::assertSame(UserAdminUrl::class, $helpers['aliases']['userAdminUrl']);
        self::assertArrayHasKey(UserUrl::class, $helpers['factories']);
        self::assertArrayHasKey(UserAdminUrl::class, $helpers['factories']);
    }

    protected function setUp(): void
    {
        $this->provider = new ConfigProvider();
    }
}
