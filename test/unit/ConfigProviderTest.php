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
use Webware\UserManager\Acl\RuleSeeds;
use Webware\UserManager\Admin\Dashboard\RegisterWidgetListener;
use Webware\UserManager\Command\ActivateUserCommand;
use Webware\UserManager\Command\CreateUserCommand;
use Webware\UserManager\Command\RegenerateVerificationTokenCommand;
use Webware\UserManager\Command\ResendVerificationEmailCommand;
use Webware\UserManager\Command\SendVerificationEmailCommand;
use Webware\UserManager\Command\SetPasswordCommand;
use Webware\UserManager\Command\ToggleUserActiveCommand;
use Webware\UserManager\Command\UpdateUserCommand;
use Webware\UserManager\ConfigProvider;
use Webware\UserManager\Console\InitDbCommand;
use Webware\UserManager\Container\UserFactory;
use Webware\UserManager\Entity\User;
use Webware\UserManager\Event\SendVerificationEmailEvent;
use Webware\UserManager\InputFilter\CreateUserDataFilter;
use Webware\UserManager\InputFilter\RegistrationDataFilter;
use Webware\UserManager\InputFilter\UpdateUserDataFilter;
use Webware\UserManager\Listener\SendVerificationEmailListener;
use Webware\UserManager\Query\AuthenticateUserQuery;
use Webware\UserManager\Query\CheckUserActiveQuery;
use Webware\UserManager\Query\FetchAssignableRolesQuery;
use Webware\UserManager\Query\FetchUserByEmailQuery;
use Webware\UserManager\Query\FetchUserByIdQuery;
use Webware\UserManager\Query\FetchUserByVerificationTokenQuery;
use Webware\UserManager\Query\FetchUsersQuery;
use Webware\UserManager\QueryHandler\AuthenticateUserHandler;
use Webware\UserManager\QueryHandler\CheckUserActiveHandler;
use Webware\UserManager\QueryHandler\FetchAssignableRolesHandler;
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
                'roles'               => [
                    'Guest'  => [],
                    'Member' => ['Guest'],
                ],
                'resources'           => [
                    'user.session.read'               => true,
                    'user.session.create'             => true,
                    'user.register.read'              => true,
                    'user.register.create'            => true,
                    'user.verify.email.read'          => true,
                    'user.resend.verification.read'   => true,
                    'user.resend.verification.create' => true,
                    'user.logout.read'                => true,
                    'user.account.read'               => true,
                    'admin.user'                      => true,
                    'admin.user.create'               => true,
                    'admin.user.create.modal'         => true,
                    'admin.user.update'               => true,
                    'admin.user.toggle.update'        => true,
                ],
                'rule_seed_providers' => [RuleSeeds::class],
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
        self::assertSame('/user/login', $config['redirect']);
        self::assertSame('/', $config['post_login_redirect']);
    }

    #[Test]
    public function commandMapMapsEveryCommandToAHandler(): void
    {
        $map = $this->provider->getCommandMap();

        self::assertCount(8, $map);
        self::assertArrayHasKey(ActivateUserCommand::class, $map);
        self::assertArrayHasKey(CreateUserCommand::class, $map);
        self::assertArrayHasKey(RegenerateVerificationTokenCommand::class, $map);
        self::assertArrayHasKey(ResendVerificationEmailCommand::class, $map);
        self::assertArrayHasKey(SendVerificationEmailCommand::class, $map);
        self::assertArrayHasKey(SetPasswordCommand::class, $map);
        self::assertArrayHasKey(ToggleUserActiveCommand::class, $map);
        self::assertArrayHasKey(UpdateUserCommand::class, $map);
    }

    #[Test]
    public function defaultConfigHoldsRouteAndLoginDefaults(): void
    {
        $config = $this->provider->getDefaultConfig();

        self::assertSame(['login_path' => '/user/login'], $config);
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
        self::assertArrayNotHasKey(User::class, $deps['factories']);
        self::assertArrayHasKey(User::class, $deps['invokables']);
        self::assertArrayHasKey(RuleSeeds::class, $deps['invokables']);
        self::assertArrayHasKey(InitDbCommand::class, $deps['factories']);
    }

    #[Test]
    public function inputFilterConfigRegistersEveryDataFilter(): void
    {
        $config = $this->provider->getInputFilterConfig();

        self::assertArrayHasKey(CreateUserDataFilter::class, $config['factories']);
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
        self::assertArrayHasKey('validators', $config);
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
    public function listenersRegisterTheWidgetAndVerificationListeners(): void
    {
        self::assertSame(
            [
                RegisterWidgetEvent::class        => [
                    ['listener' => RegisterWidgetListener::class, 'priority' => 1],
                ],
                SendVerificationEmailEvent::class => [
                    ['listener' => SendVerificationEmailListener::class, 'priority' => 1],
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
                FetchAssignableRolesQuery::class         => FetchAssignableRolesHandler::class,
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
                        ) . '/src/../templates/default/user',
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
