<?php

declare(strict_types=1);

namespace Tests\App\Api;

use App\Security\AccessProblems;
use App\Shared\Typed;
use PHPUnit\Framework\Attributes\DataProvider;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\ReadsResources;

/**
 * The rights of every endpoint of a member, proven by one test. Its table is the `#[IsGranted]` attributes of the
 * controllers: the roles a profile needs, and the voter of the resource (ADR 0081). Each endpoint is called in HTTP
 * with each profile, so the request crosses the firewall, the role hierarchy and the real voter. A granted request
 * stops before its controller runs: the test proves the declared rights, and changes nothing. A right that depends on
 * the state of the resource is checked in the controller, and keeps a test of its own.
 */
class EndpointRightsApiTest extends ApiTestCase
{
    use ReadsResources;

    /** The firewall the members call the API through, with an access token of the user pool. */
    private const string FIREWALL_OF_THE_MEMBERS = 'api';

    /** The answer to a request the rights let through, before its controller would run. */
    private const string GRANTED = 'granted';

    /** The answer to a caller without an access token. */
    private const string NOT_SIGNED_IN = '401';

    /** The answer to a member whose roles do not allow the intent. */
    private const string REFUSED = '403 '.AccessProblems::ROLE_NOT_ALLOWED;

    /**
     * The answer to a member of another account, by the beginning of the path of the resource: the resource is
     * unknown to them.
     */
    private const array UNKNOWN = [
        '/api/organizations/{id}' => '404 unknownOrganization',
        '/api/exchanges/{id}' => '404 unknownExchange',
        '/api/to-dos/{id}' => '404 unknownToDo',
        '/api/campaigns/{id}' => '404 unknownCampaign',
    ];

    /** Between the attributes of the controller (`#[IsGranted]` included) and the resolution of its input. */
    private const int BEFORE_THE_INPUT = -10050;

    /** The values of the variables of a path that name no resource of the account. */
    private const array VARIABLES = ['tag' => 'ESN', 'phoneNumber' => '0612345678'];

    /**
     * The profiles of the members: the roles a member is registered with, and the roles they hold. An administrator
     * may do what any member may.
     *
     * @return iterable<string, array{list<Role>, list<Role>}>
     */
    public static function profiles(): iterable
    {
        yield 'assistant' => [[Role::User], [Role::User]];
        yield 'prospector' => [[Role::User, Role::Prospector], [Role::User, Role::Prospector]];
        yield 'prospector with the personal data' => [[Role::User, Role::Prospector, Role::PersonalData], [Role::User, Role::Prospector, Role::PersonalData]];
        yield 'administrator' => [[Role::User, Role::Admin], Role::cases()];
    }

    /**
     * @param list<Role> $registered
     * @param list<Role> $held
     */
    #[DataProvider('profiles')]
    public function testEachEndpointLetsThroughTheProfilesItsAttributesGrantAndRefusesTheOthers(array $registered, array $held): void
    {
        $paths = $this->pathsOfTheResourcesOfTheAccount();
        $this->login($this->aMemberOfTheFixtures('profile', ...$registered));
        $this->stopEachGrantedRequestBeforeItsController();
        $roles = array_map(static fn (Role $role): string => $role->value, $held);

        $endpoints = self::endpointsOfTheMembers();
        $this->assertNotEmpty($endpoints);

        $this->assertSame(array_map(static fn (Route $route): string => [] === array_diff(self::rolesOf($route), $roles) ? self::GRANTED : self::REFUSED, $endpoints), array_map(fn (Route $route): string => $this->answerTo($route, $paths), $endpoints));
    }

    public function testEachEndpointOfAResourceDoesNotFindTheResourceOfAnotherAccount(): void
    {
        $paths = $this->pathsOfTheResourcesOfTheAccount();
        $this->login($this->aMemberOfAnotherAccount('competitor'));
        $this->stopEachGrantedRequestBeforeItsController();

        $ofAResource = array_filter(self::endpointsOfTheMembers(), static fn (Route $route): bool => [] !== self::votersOf($route));
        $this->assertNotEmpty($ofAResource);

        $this->assertSame(array_map(self::unknownTo(...), $ofAResource), array_map(fn (Route $route): string => $this->answerTo($route, $paths), $ofAResource));
    }

    public function testEachEndpointOfAMemberRefusesWhoeverIsNotSignedIn(): void
    {
        $paths = $this->pathsOfTheResourcesOfTheAccount();
        $this->client->setServerParameter('HTTP_AUTHORIZATION', '');
        $this->stopEachGrantedRequestBeforeItsController();
        $endpoints = self::endpointsOfTheMembers();

        $this->assertSame(array_map(static fn (): string => self::NOT_SIGNED_IN, $endpoints), array_map(fn (Route $route): string => $this->answerTo($route, $paths), $endpoints));
    }

    public function testEachEndpointOfAMemberDeclaresItsRights(): void
    {
        $withoutRights = array_filter(self::endpointsOfTheMembers(), static fn (Route $route): bool => [] === self::isGrantedOf($route));

        $this->assertSame([], array_keys($withoutRights), 'Endpoints of a member whose controller declares no #[IsGranted]');
    }

    /**
     * The resources of the account of the demonstration that the paths name by their `id`: Babbage & Co, its contact
     * Ada Lovelace, an exchange with her, a to-do and a campaign.
     *
     * @return array<string, string> the id by the beginning of the path
     */
    private function pathsOfTheResourcesOfTheAccount(): array
    {
        $this->login();
        $babbage = self::stringAt($this->requestJson('GET', '/api/organizations?q=Babbage'), 'items', 0, 'id');
        $ada = self::stringAt($this->requestJson('GET', '/api/contacts?q=Lovelace'), 'items', 0, 'id');
        $this->requestJson('POST', '/api/contacts/'.$ada.'/tags', ['tags' => ['#CLIENT']]);

        return [
            '/api/organizations/{id}' => $babbage,
            '/api/contacts/{id}' => $ada,
            '/api/exchanges/{id}' => self::stringAt($this->requestJson('GET', '/api/contacts/'.$ada.'/exchanges'), 'items', 0, 'id'),
            '/api/to-dos/{id}' => self::stringAt($this->requestJson('POST', '/api/organizations/'.$babbage.'/to-dos', ['text' => 'Préparer la proposition', 'dueOn' => '2026-10-20']), 'id'),
            '/api/campaigns/{id}' => self::stringAt($this->requestJson('POST', '/api/campaigns', ['name' => 'Relance des clients', 'channel' => 'email', 'tags' => ['#CLIENT'], 'message' => 'Bonjour {firstName}', 'subject' => '{firstName}, une question']), 'id'),
        ];
    }

    /**
     * A request the rights let through gets its status at once: its controller does not run, nor does the
     * validation of its input.
     */
    private function stopEachGrantedRequestBeforeItsController(): void
    {
        $this->keepTheKernel();
        self::service(EventDispatcherInterface::class, 'event_dispatcher')->addListener(KernelEvents::CONTROLLER_ARGUMENTS, static function (ControllerArgumentsEvent $event): void {
            $event->setController(static fn (): Response => new Response(self::GRANTED));
            $event->setArguments([]);
            $event->stopPropagation();
        }, self::BEFORE_THE_INPUT);
    }

    /**
     * @param array<string, string> $paths
     */
    private function answerTo(Route $route, array $paths): string
    {
        $this->client->request($route->getMethods()[0], self::pathOf($route, $paths), server: ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json']);
        $response = $this->client->getResponse();

        return self::GRANTED === $response->getContent() ? self::GRANTED : trim(sprintf('%d %s', $response->getStatusCode(), Typed::string($this->responseData()['reason'] ?? '')));
    }

    private static function unknownTo(Route $route): string
    {
        return array_find(self::UNKNOWN, static fn (string $answer, string $beginning): bool => str_starts_with($route->getPath(), $beginning)) ?? throw new \LogicException(sprintf('No unknown resource for %s', $route->getPath()));
    }

    /**
     * @param array<string, string> $paths
     */
    private static function pathOf(Route $route, array $paths): string
    {
        $path = $route->getPath();
        foreach (array_map(Typed::string(...), $route->compile()->getPathVariables()) as $variable) {
            $path = str_replace('{'.$variable.'}', self::valueOf($route, $variable, $paths), $path);
        }

        return $path;
    }

    /**
     * @param array<string, string> $paths
     */
    private static function valueOf(Route $route, string $variable, array $paths): string
    {
        foreach ($paths as $beginning => $id) {
            if ('id' === $variable && str_starts_with($route->getPath(), $beginning)) {
                return $id;
            }
        }

        return self::VARIABLES[$variable] ?? (Requirement::UUID === $route->getRequirement($variable) ? self::newIdentity() : throw new \LogicException(sprintf('No value for {%s} in %s', $variable, $route->getPath())));
    }

    /**
     * @return array<string, Route> the routes the members call through their firewall, by the name of the route
     */
    private static function endpointsOfTheMembers(): array
    {
        $routes = Typed::object(self::getContainer()->get(RouterInterface::class), RouterInterface::class)->getRouteCollection()->all();
        $security = self::service(Security::class, 'security.helper');

        return array_filter($routes, static fn (Route $route): bool => str_starts_with(Typed::string($route->getDefault('_controller')), 'App\\') && self::FIREWALL_OF_THE_MEMBERS === $security->getFirewallConfig(Request::create($route->getPath()))?->getName());
    }

    /**
     * @return list<string> the roles that the controller's `#[IsGranted]` require
     */
    private static function rolesOf(Route $route): array
    {
        return array_values(array_filter(self::isGrantedOf($route), static fn (string $attribute): bool => str_starts_with($attribute, 'ROLE_')));
    }

    /**
     * @return list<string> the voters that the controller's `#[IsGranted]` ask about its resource
     */
    private static function votersOf(Route $route): array
    {
        return array_values(array_diff(self::isGrantedOf($route), self::rolesOf($route)));
    }

    /**
     * @return list<string> the attributes of the `#[IsGranted]` of the controller's class and of its `__invoke`
     */
    private static function isGrantedOf(Route $route): array
    {
        $name = explode('::', Typed::string($route->getDefault('_controller')))[0];
        $controller = class_exists($name) ? new \ReflectionClass($name) : throw new \LogicException(sprintf('No controller class %s', $name));
        $attributes = [...$controller->getAttributes(IsGranted::class), ...$controller->getMethod('__invoke')->getAttributes(IsGranted::class)];

        return array_map(static fn (\ReflectionAttribute $attribute): string => Typed::string($attribute->newInstance()->attribute), $attributes);
    }
}
