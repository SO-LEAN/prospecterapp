<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\ReadsResources;
use Tests\App\Double\OutsideServices;

/**
 * The establishments of the directory of the companies an organization may be, asked to its double (ADR 0092).
 */
#[Group('api')]
class EstablishmentsApiTest extends ApiTestCase
{
    use ReadsResources;

    private const array ACME_COMPANY = [
        'siren' => '552100554',
        'nom_complet' => 'ACME',
        'matching_etablissements' => [['siret' => '55210055400021', 'adresse' => '17 RUE DOCTEUR BOUCHUT 69003 LYON', 'commune' => '69383', 'activite_principale' => '62.02A', 'etat_administratif' => 'F']],
    ];

    private const array ACME_IN_LYON = [
        'siret' => '55210055400021',
        'name' => 'ACME',
        'address' => '17 RUE DOCTEUR BOUCHUT 69003 LYON',
        'activity' => '62.02A',
        'closed' => true,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testTheDirectoryProposesTheEstablishmentsOfTheOrganization(): void
    {
        $id = $this->anOrganizationOfTheAccount('ACME');
        $this->outsideServices()->directoryLists(self::ACME_COMPANY);

        $proposed = $this->requestJson('GET', '/api/organizations/'.$id.'/establishments');

        $this->assertResponseIsSuccessful();
        $this->assertSame(['establishments' => [self::ACME_IN_LYON]], $proposed);
    }

    /**
     * @param list<Role> $roles
     */
    #[DataProvider('everyProspector')]
    public function testEveryProspectorSearchesWithOrWithoutTheRight(string $member, array $roles): void
    {
        $id = $this->anOrganizationOfTheAccount('ACME');
        $this->login($this->aMemberOfTheFixtures($member, ...$roles));
        $this->outsideServices()->directoryLists(self::ACME_COMPANY);

        $this->requestJson('GET', '/api/organizations/'.$id.'/establishments');

        $this->assertResponseIsSuccessful();
    }

    /**
     * @return iterable<string, array{string, list<Role>}>
     */
    public static function everyProspector(): iterable
    {
        yield 'without the right to manage personal data' => ['prospector-without-the-right', [Role::User, Role::Prospector]];
        yield 'an administrator' => ['administrator', [Role::Prospector, Role::Admin]];
    }

    public function testTheOrganizationLeadsToItsEstablishmentsWhereItsIdentityIsRevised(): void
    {
        $id = $this->anOrganizationOfTheAccount('ACME');

        $organization = $this->requestJson('GET', '/api/organizations/'.$id);

        $this->assertSame('/api/organizations/'.$id.'/establishments', self::valueAt($organization, '_links', 'searchEstablishments', 'href'));
    }

    public function testTheDirectoryOutOfReachIsToTryAgainLater(): void
    {
        $id = $this->anOrganizationOfTheAccount('ACME');

        $problem = $this->requestJson('GET', '/api/organizations/'.$id.'/establishments');

        $this->assertResponseStatusCodeSame(503);
        $this->assertSame('directoryUnreachable', $problem['reason']);
    }

    public function testAnOrganizationOfAnotherAccountIsNotFound(): void
    {
        $problem = $this->requestJson('GET', '/api/organizations/'.$this->anOrganizationOfAnotherAccount().'/establishments');

        $this->assertResponseStatusCodeSame(404);
        $this->assertSame('unknownOrganization', $problem['reason']);
    }

    public function testTheSearchRequiresAnAccessToken(): void
    {
        $id = $this->anOrganizationOfTheAccount('ACME');
        $this->client->setServerParameter('HTTP_AUTHORIZATION', '');

        $this->requestJson('GET', '/api/organizations/'.$id.'/establishments');

        $this->assertResponseStatusCodeSame(401);
    }

    private function anOrganizationOfAnotherAccount(): string
    {
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $organization = Organization::prospectedBy(new Gesture($prospector->accountId, $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)), new OrganizationId(self::newIdentity()), new LegalIdentity('ACME'), new ContactDetails());
        self::service(OrganizationRepository::class)->save($organization);

        return $organization->id()->value;
    }

    /**
     * Saved without a request: the double of the directory, told what to answer, keeps it for the first request of the
     * test only, the kernel resetting it when it boots again for the next one.
     */
    private function anOrganizationOfTheAccount(string $corporateName): string
    {
        $prospector = new Prospector(new MemberId($this->memberIdOf('prospector-user')), $this->accountOfTheFixtures());
        $organization = Organization::prospectedBy(new Gesture($prospector->accountId, $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)), new OrganizationId(self::newIdentity()), new LegalIdentity($corporateName), new ContactDetails());
        self::service(OrganizationRepository::class)->save($organization);

        return $organization->id()->value;
    }

    private function outsideServices(): OutsideServices
    {
        return self::service(OutsideServices::class);
    }
}
