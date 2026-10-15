<?php

namespace Tests\App\Base;

use App\DataFixtures\AppFixtures;
use App\Shared\Identities;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Builder;
use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\Accounts\Domain\Role;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Purpose;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * The API as the SPA uses it, on a database recreated for each test with the fixtures. A member calls it with
 * an access token signed like the authorization server signs them (the test key pair of tests/bootstrap.php).
 */
abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected ReferenceRepository $fixtures;

    protected function setUp(): void
    {
        $this->client = static::createClient([], ['HTTP_HOST' => 'api.test.prospecter.io']);
        $this->fixtures = $this->loadFixtures();
    }

    protected function login(string $reference = 'prospector-user'): void
    {
        $memberId = $this->fixtures->getReference($reference, Member::class)->id()->value;
        $this->forgetTheLoadedAggregates();
        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$this->accessTokenOf($memberId));
    }

    /**
     * The account of the fixtures, SOLEAN IT, by its identity only: its lazy reference must not stay loaded
     * while a request reads the account (see forgetTheLoadedAggregates()).
     */
    protected function accountOfTheFixtures(): AccountId
    {
        $accountId = $this->fixtures->getReference('solean-it-account', Account::class)->id();
        $this->forgetTheLoadedAggregates();

        return $accountId;
    }

    /**
     * Another member of the account of the fixtures, with these roles, known under this reference.
     */
    protected function aMemberOfTheFixtures(string $reference, Role ...$roles): string
    {
        $account = $this->accountOfTheFixtures();
        $profile = Person::collect(new PersonId(self::newIdentity()), $account, Purpose::Membership, new \DateTimeImmutable());
        self::getContainer()->get(PersonRepository::class)->save($profile);
        $member = Member::register(new MemberId(self::newIdentity()), $account, $profile->id(), ...$roles);
        self::getContainer()->get(MemberRepository::class)->save($member);
        $this->fixtures->setReference($reference, $member);

        return $reference;
    }

    protected function accessTokenOf(string $memberId, string $audience = 'prospecter-front', string $expiresIn = '+15 minutes'): string
    {
        $now = new \DateTimeImmutable();

        return new Builder(new JoseEncoder(), ChainedFormatter::default())
            ->issuedBy('test')
            ->permittedFor($audience)
            ->relatedTo($memberId)
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify($expiresIn))
            ->getToken(new Sha256(), InMemory::file(dirname(__DIR__, 2).'/var/test-jwt/private.pem'))
            ->toString();
    }

    /**
     * An identity for what a test arranges, as the controllers generate them.
     */
    protected static function newIdentity(): string
    {
        return new Identities()->next();
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

        return '' === $content ? [] : json_decode($content, true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed> $problem
     *
     * @return list<string>
     */
    protected function violatedFields(array $problem): array
    {
        return array_map(static fn (array $violation): string => $violation['propertyPath'], $problem['violations'] ?? []);
    }

    /**
     * The fixtures are written by the repositories, each in the database of its context (ADR 0038); the
     * references belong to Accounts, the context of the members the tests sign in.
     */
    private function loadFixtures(): ReferenceRepository
    {
        array_map($this->recreateTheSchemaOf(...), $this->entityManagers());

        $loader = new Loader();
        $loader->addFixture(static::getContainer()->get(AppFixtures::class));
        $executor = new ORMExecutor($this->entityManagers()['accounts']);
        $executor->execute($loader->getFixtures(), append: true);
        $this->forgetTheLoadedAggregates();

        return $executor->getReferenceRepository();
    }

    private function recreateTheSchemaOf(EntityManagerInterface $entityManager): void
    {
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());
    }

    /**
     * A fixture reference is a lazy proxy kept in the identity map: initializing it would overwrite its
     * readonly identity with an equal but new value object, which Doctrine refuses. The requests load the
     * aggregates again.
     */
    private function forgetTheLoadedAggregates(): void
    {
        foreach ($this->entityManagers() as $entityManager) {
            $entityManager->clear();
        }
    }

    /**
     * @return array<string, EntityManagerInterface> by context
     */
    private function entityManagers(): array
    {
        return static::getContainer()->get('doctrine')->getManagers();
    }
}
