<?php

declare(strict_types=1);

namespace Tests\App\Base;

use App\Demo\DemoCredentials;
use App\Demo\DemoData;
use App\Shared\Identities;
use App\Shared\Messaging\CommitOutbox;
use App\Shared\Persistence\DynamoUnitOfWork;
use App\Shared\Persistence\RevisionsRead;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use App\SignIn\Credentials;
use App\SignIn\UserPool;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Prospecter\Core\Accounts\Domain\Model\Account;
use Prospecter\Core\Accounts\Domain\Model\Member;
use Prospecter\Core\Accounts\Domain\Model\ProfileId;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Accounts\Domain\Repository\AccountRepository;
use Prospecter\Core\Accounts\Domain\Repository\MemberRepository;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\Company\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\Gesture\Gesture;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * The API as the SPA uses it, each test starting from the data of the demonstration (ADR 0048). A member calls it
 * with a real access token of the test user pool of MiniStack, each test starting from the members of the
 * demonstration (ADR 0052).
 */
abstract class ApiTestCase extends WebTestCase
{
    /** The moment of the clock of the controllers (`clock` of `when@test`): that of a test naming no other. */
    protected const string THE_MOMENT_OF_THE_TESTS = '2026-10-15 09:00 UTC';

    protected KernelBrowser $client;

    /**
     * @var array<string, string> the members the tests sign in, by reference
     */
    private array $members = ['prospector-user' => DemoData::PROSPECTOR, 'assistant-user' => DemoData::ASSISTANT];

    protected function setUp(): void
    {
        $this->client = static::createClient([], ['HTTP_HOST' => 'api.test.prospecter.io']);
        self::service(DataOfTheDemonstration::class)->restore();
        self::service(MembersOfTheDemonstration::class)->restore();
    }

    /**
     * The kernel kept between the requests, for a double to answer each: the tokens the readers sign in with later
     * are checked on the clock of the tokens frozen when it booted, set a minute later so that none is issued after it.
     */
    protected function keepTheKernel(): void
    {
        $this->client->disableReboot();
        self::service(MockClock::class, 'app.token_clock')->modify('+1 minute');
    }

    protected function login(string $reference = 'prospector-user'): void
    {
        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$this->tokensOf($this->members[$reference])['AccessToken']);
    }

    /**
     * The account of the demonstration, SOLEAN IT.
     */
    protected function accountOfTheFixtures(): AccountId
    {
        return new AccountId(DemoData::SOLEAN_IT);
    }

    /**
     * Another member of the account of the fixtures, with these roles, known under this reference.
     */
    protected function aMemberOfTheFixtures(string $reference, Role ...$roles): string
    {
        $account = $this->accountOfTheFixtures();
        $profile = Person::collect(new Gesture($account, new MemberId(self::newIdentity()), new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)), new PersonId(self::newIdentity()), Purpose::Membership);
        self::service(PersonRepository::class)->save($profile);
        $member = Member::register(new MemberId(self::newIdentity()), $account, new ProfileId($profile->id()->value), ...$roles);
        self::service(MemberRepository::class)->save($member);
        $this->members[$reference] = $member->id()->value;
        self::service(Credentials::class)->register($member->id()->value, $member->id()->value.'@solean-it.io', DemoCredentials::PASSWORD);

        return $reference;
    }

    /**
     * A prospector managing the personal data of an account other than that of the fixtures, under this reference:
     * who reaches nothing of SOLEAN IT.
     */
    protected function aMemberOfAnotherAccount(string $reference): string
    {
        $account = Account::open(new AccountId(self::newIdentity()), new LegalIdentity('Another account', 'SAS'), new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        self::service(AccountRepository::class)->save($account);
        $profile = Person::collect(new Gesture($account->id(), new MemberId(self::newIdentity()), new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)), new PersonId(self::newIdentity()), Purpose::Membership);
        self::service(PersonRepository::class)->save($profile);
        $member = Member::register(new MemberId(self::newIdentity()), $account->id(), new ProfileId($profile->id()->value), Role::User, Role::Prospector, Role::PersonalData);
        self::service(MemberRepository::class)->save($member);
        $this->members[$reference] = $member->id()->value;
        self::service(Credentials::class)->register($member->id()->value, $member->id()->value.'@another.test', DemoCredentials::PASSWORD);

        return $reference;
    }

    /**
     * The id of a member known under this reference, to arrange what they did through the repositories.
     */
    protected function memberIdOf(string $reference): string
    {
        return $this->members[$reference] ?? throw new \LogicException(sprintf('No member known as %s', $reference));
    }

    /**
     * The tokens the user pool gives a member signing in with the password of the demonstration, for its
     * client or another one.
     *
     * @return array{AccessToken: string, IdToken: string, RefreshToken: string}
     */
    protected function tokensOf(string $memberId, ?string $clientId = null): array
    {
        $pool = self::service(UserPool::class);

        $result = Typed::document(self::service(CognitoIdentityProviderClient::class)->adminInitiateAuth([
            'UserPoolId' => $pool->id(),
            'ClientId' => $clientId ?? $pool->clientId(),
            'AuthFlow' => 'ADMIN_USER_PASSWORD_AUTH',
            'AuthParameters' => ['USERNAME' => $memberId, 'PASSWORD' => DemoCredentials::PASSWORD],
        ])['AuthenticationResult']);

        return ['AccessToken' => Typed::string($result['AccessToken']), 'IdToken' => Typed::string($result['IdToken']), 'RefreshToken' => Typed::string($result['RefreshToken'])];
    }

    /**
     * An identity for what a test arranges, as the controllers generate them.
     */
    protected static function newIdentity(): string
    {
        return new Identities()->next();
    }

    /**
     * The same table, with the unit of work of another request: what it writes is sent at once, whatever the unit
     * of the test.
     */
    protected function asAnotherRequest(Table $table): Table
    {
        $client = self::service(DynamoDbClient::class);
        $marshaler = self::service(Marshaler::class);

        return new Table($client, $marshaler, new DynamoUnitOfWork($client, new CommitOutbox([], $marshaler, new Identities(), self::service(MockClock::class, 'app.commit_clock'))), new RevisionsRead(), $table->name);
    }

    /**
     * @param array<string, mixed>|null $payload
     *
     * @return array<string, mixed>
     */
    protected function requestJson(string $method, string $uri, ?array $payload = null): array
    {
        $this->client->request($method, $uri, server: ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'], content: null === $payload ? null : json_encode($payload, JSON_THROW_ON_ERROR));

        return $this->responseData();
    }

    /**
     * @return array<string, mixed>
     */
    protected function uploadImage(string $uri): array
    {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        copy(__DIR__.'/../Fixtures/logo.png', $path);

        $this->client->request('POST', $uri, files: ['file' => new UploadedFile($path, 'logo.png', 'image/png', test: true)], server: ['HTTP_ACCEPT' => 'application/json']);

        return $this->responseData();
    }

    /**
     * @return array<string, mixed>
     */
    protected function responseData(): array
    {
        $content = (string) $this->client->getResponse()->getContent();

        return '' === $content ? [] : Typed::document(json_decode($content, true, flags: JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string, mixed> $problem
     *
     * @return list<string>
     */
    protected function violatedFields(array $problem): array
    {
        return array_map(static fn (array $violation): string => Typed::string($violation['propertyPath']), Typed::documents($problem['violations'] ?? []));
    }

    /**
     * Gets the test container's service of this class, under its class name or under the given id.
     *
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    protected static function service(string $class, ?string $id = null): object
    {
        $service = self::getContainer()->get($id ?? $class);
        self::assertInstanceOf($class, $service);

        return $service;
    }

    /**
     * Gets a context's table as the test container wires it.
     */
    protected static function tableOf(string $context): Table
    {
        return self::service(Table::class, 'app.table.'.$context);
    }

    /**
     * Gets the item stored under these keys. The test fails when there is none.
     *
     * @return array<string, mixed>
     */
    protected static function storedItem(Table $table, string $partitionKey, string $sortKey): array
    {
        $item = $table->item($partitionKey, $sortKey);
        self::assertNotNull($item, sprintf('No item under %s and %s', $partitionKey, $sortKey));

        return $item;
    }

    /**
     * Stopping prospecting erases the organization at once, its exchanges by a reaction after it: the state in between.
     */
    protected function eraseBeforeItsExchanges(string $organization): void
    {
        $organizations = self::service(OrganizationRepository::class);
        $organizations->remove($organizations->get(new OrganizationId($organization)));
    }
}
