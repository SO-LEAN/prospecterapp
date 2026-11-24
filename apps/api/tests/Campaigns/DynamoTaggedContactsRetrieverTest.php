<?php

declare(strict_types=1);

namespace Tests\App\Campaigns;

use Prospecter\Core\Campaigns\Application\Port\ContactTagSources;
use Prospecter\Core\Campaigns\Application\Port\TaggedContactsRetriever;
use Prospecter\Core\Campaigns\Application\Tagging\FactRecorder;
use Prospecter\Core\Campaigns\Domain\Model\ContactFact;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Model\Locality;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationFact;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationId;
use Prospecter\Core\Campaigns\Domain\Model\Reach;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\Campaigns\Domain\Model\Tagging;
use Prospecter\Core\Campaigns\Domain\Repository\TaggingRepository;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Tests\App\Base\ApiTestCase;

/**
 * What the tags of every contact of an account derive from, read in its partition at once to search them by their
 * tags: each contact its fact keeps, its tagging, the taggings of the organizations linked and where they are (ADR
 * 0142), its reach.
 */
class DynamoTaggedContactsRetrieverTest extends ApiTestCase
{
    private const string ILLKIRCH = '67218';

    private AccountId $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->account = new AccountId(self::newIdentity());
    }

    public function testEveryContactKeptComesWithWhatItsTagsDeriveFrom(): void
    {
        $acme = new OrganizationId(self::newIdentity());
        $ada = new ContactId(self::newIdentity());
        $grace = new ContactId(self::newIdentity());
        $this->recorder()->record(new OrganizationFact($this->account, $acme, 1, true, [$ada, $grace], new Locality(self::ILLKIRCH, 'FR')));
        $this->recorder()->record(new ContactFact($this->account, $ada, 1, true, new Reach(true, false, false, false)));
        $this->recorder()->record(new ContactFact($this->account, $grace, 1, true, Reach::none()));
        $this->tag(Holder::organization($acme), '#ESN');
        $this->tag(Holder::contact($grace), '#DECIDEUR');
        $illkirch = ['municipalityCode' => self::ILLKIRCH, 'country' => 'FR'];

        $this->assertEquals([
            new ContactTagSources($ada->value, [], [$acme->value => ['ESN']], ['email' => true, 'phone' => false, 'mobile' => false, 'linkedIn' => false], [$acme->value => $illkirch]),
            new ContactTagSources($grace->value, ['DECIDEUR'], [$acme->value => ['ESN']], ['email' => false, 'phone' => false, 'mobile' => false, 'linkedIn' => false], [$acme->value => $illkirch]),
        ], $this->sorted($this->retriever()->sourcesOfEveryContact($this->account)));
    }

    public function testAContactForgottenIsLeftOut(): void
    {
        $ada = new ContactId(self::newIdentity());
        $this->recorder()->record(new ContactFact($this->account, $ada, 1, true, Reach::none()));

        $this->recorder()->record(new ContactFact($this->account, $ada, 0, false, Reach::none()));

        $this->assertSame([], $this->retriever()->sourcesOfEveryContact($this->account));
    }

    public function testTheContactsOfAnotherAccountAreLeftOut(): void
    {
        $other = new AccountId(self::newIdentity());
        $this->recorder()->record(new ContactFact($other, new ContactId(self::newIdentity()), 1, true, Reach::none()));

        $this->assertSame([], $this->retriever()->sourcesOfEveryContact($this->account));
    }

    private function tag(Holder $holder, string $tag): void
    {
        $tagging = Tagging::of($this->account, $holder, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $tagging->tagWith([Tag::written($tag)], by: new MemberId(self::newIdentity()), at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $this->taggings()->save($tagging);
    }

    /**
     * @param list<ContactTagSources> $sources
     *
     * @return list<ContactTagSources>
     */
    private function sorted(array $sources): array
    {
        usort($sources, static fn (ContactTagSources $one, ContactTagSources $other): int => strcmp($one->contactId, $other->contactId));

        return $sources;
    }

    private function recorder(): FactRecorder
    {
        return self::service(FactRecorder::class);
    }

    private function taggings(): TaggingRepository
    {
        return self::service(TaggingRepository::class);
    }

    private function retriever(): TaggedContactsRetriever
    {
        return self::service(TaggedContactsRetriever::class);
    }
}
