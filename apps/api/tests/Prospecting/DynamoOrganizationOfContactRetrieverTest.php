<?php

declare(strict_types=1);

namespace Tests\App\Prospecting;

use App\Prospecting\Persistence\OrganizationKeys;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Application\Contact\ConsultOrganizationsOfContacts\OrganizationOfContact;
use Prospecter\Core\Prospecting\Application\Port\OrganizationOfContactRetriever;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\Company\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\Contact\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Gesture\Gesture;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Tests\App\Base\ApiTestCase;

/**
 * The organizations naming each contact, read from the links the repository writes, without restoring them
 * (ADR 0088).
 */
class DynamoOrganizationOfContactRetrieverTest extends ApiTestCase
{
    private const string ATTACHED_AT = '2026-11-24 09:00 UTC';

    public function testEachContactComesWithTheOrganizationsOfTheAccountNamingThem(): void
    {
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $ada = new ContactId(self::newIdentity());
        $grace = new ContactId(self::newIdentity());
        $alone = new ContactId(self::newIdentity());
        $acme = $this->prospect($prospector, 'ACME', $ada, $grace);
        $group = $this->prospect($prospector, 'Groupe ACME', $ada);
        $this->prospect(new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity())), 'Another account', $ada);

        $organizations = self::service(OrganizationOfContactRetriever::class)->ofContacts([$ada, $grace, $alone], $prospector->accountId);

        $this->assertEqualsCanonicalizing([$acme->id()->value, $group->id()->value], self::idsOf($organizations[$ada->value]));
        $this->assertSame([$acme->id()->value], self::idsOf($organizations[$grace->value]));
        $this->assertSame('ACME', $organizations[$grace->value][0]->fullName);
        $this->assertSame([], $organizations[$alone->value]);
    }

    public function testEachOrganizationSaysWhenTheContactWasAttachedToIt(): void
    {
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $ada = new ContactId(self::newIdentity());
        $acme = $this->prospect($prospector, 'ACME');
        $acme->addContact($ada, $prospector->id, new \DateTimeImmutable(self::ATTACHED_AT));
        self::service(OrganizationRepository::class)->save($acme);

        $organizations = self::service(OrganizationOfContactRetriever::class)->ofContacts([$ada], $prospector->accountId);

        $this->assertEquals(new \DateTimeImmutable(self::ATTACHED_AT), $organizations[$ada->value][0]->attachedAt);
    }

    public function testAnAttachmentWrittenBeforeTheMomentWasKeptHasNone(): void
    {
        $prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()));
        $ada = new ContactId(self::newIdentity());
        $acme = $this->prospect($prospector, 'ACME', $ada);
        $this->writtenBeforeTheMoments($acme);

        $organizations = self::service(OrganizationOfContactRetriever::class)->ofContacts([$ada], $prospector->accountId);

        $this->assertEquals([[$acme->id()->value, 'ACME', null]], array_map(static fn (OrganizationOfContact $organization): array => [$organization->id, $organization->fullName, $organization->attachedAt], $organizations[$ada->value]));
    }

    private function prospect(Prospector $prospector, string $corporateName, ContactId ...$contacts): Organization
    {
        $organization = Organization::prospectedBy(new Gesture($prospector->accountId, $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)), new OrganizationId(self::newIdentity()), new LegalIdentity($corporateName), new ContactDetails());

        foreach ($contacts as $contact) {
            $organization->addContact($contact, $prospector->id, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        }

        self::service(OrganizationRepository::class)->save($organization);

        return $organization;
    }

    /**
     * @param list<OrganizationOfContact> $organizations
     *
     * @return list<string>
     */
    private static function idsOf(array $organizations): array
    {
        return array_map(static fn (OrganizationOfContact $organization): string => $organization->id, $organizations);
    }

    /**
     * Its document as it was written before Prospecter kept when each contact was attached.
     */
    private function writtenBeforeTheMoments(Organization $organization): void
    {
        $item = self::storedItem(self::tableOf('prospecting'), OrganizationKeys::organization($organization->id()->value), OrganizationKeys::ORGANIZATION);
        $document = Typed::document($item['organization']);
        unset($document['attachedAt']);
        $item['organization'] = $document;
        self::tableOf('prospecting')->batch([$item], []);
    }
}
