<?php

namespace Tests\App\Campaigns;

use Prospecter\Core\Campaigns\Application\FactRecorder;
use Prospecter\Core\Campaigns\Application\Port\ContactTagSources;
use Prospecter\Core\Campaigns\Application\Port\TaggedContactsRetriever;
use Prospecter\Core\Campaigns\Domain\Model\ContactFact;
use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationFact;
use Prospecter\Core\Campaigns\Domain\Model\Reach;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\Campaigns\Domain\Model\Tagging;
use Prospecter\Core\Campaigns\Domain\Repository\TaggingRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * What the tags of every contact of an account derive from, read in its partition at once to search them by their
 * tags: each contact its fact keeps, its tagging, the taggings of the organizations linked, its reach.
 */
class DynamoTaggedContactsRetrieverTest extends ApiTestCase
{
    private AccountId $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->account = new AccountId(self::newIdentity());
    }

    public function testEveryContactKeptComesWithWhatItsTagsDeriveFrom(): void
    {
        $acme = new OrganizationId(self::newIdentity());
        $ada = new PersonId(self::newIdentity());
        $grace = new PersonId(self::newIdentity());
        $this->recorder()->record(new OrganizationFact($this->account, $acme, 1, true, [$ada, $grace]));
        $this->recorder()->record(new ContactFact($this->account, $ada, 1, true, new Reach(true, false, false, false)));
        $this->recorder()->record(new ContactFact($this->account, $grace, 1, true, Reach::none()));
        $this->tag(Holder::organization($acme), '#ESN');
        $this->tag(Holder::contact($grace), '#DECIDEUR');

        $this->assertEquals([
            new ContactTagSources($ada->value, [], [$acme->value => ['ESN']], ['email' => true, 'phone' => false, 'mobile' => false, 'linkedIn' => false]),
            new ContactTagSources($grace->value, ['DECIDEUR'], [$acme->value => ['ESN']], ['email' => false, 'phone' => false, 'mobile' => false, 'linkedIn' => false]),
        ], $this->sorted($this->retriever()->sourcesOfEveryContact($this->account)));
    }

    public function testAContactForgottenIsLeftOut(): void
    {
        $ada = new PersonId(self::newIdentity());
        $this->recorder()->record(new ContactFact($this->account, $ada, 1, true, Reach::none()));

        $this->recorder()->record(new ContactFact($this->account, $ada, 0, false, Reach::none()));

        $this->assertSame([], $this->retriever()->sourcesOfEveryContact($this->account));
    }

    public function testTheContactsOfAnotherAccountAreLeftOut(): void
    {
        $other = new AccountId(self::newIdentity());
        $this->recorder()->record(new ContactFact($other, new PersonId(self::newIdentity()), 1, true, Reach::none()));

        $this->assertSame([], $this->retriever()->sourcesOfEveryContact($this->account));
    }

    private function tag(Holder $holder, string $tag): void
    {
        $tagging = Tagging::of($this->account, $holder, new \DateTimeImmutable());
        $tagging->tagWith([Tag::written($tag)], by: new MemberId(self::newIdentity()), at: new \DateTimeImmutable());
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
        return self::getContainer()->get(FactRecorder::class);
    }

    private function taggings(): TaggingRepository
    {
        return self::getContainer()->get(TaggingRepository::class);
    }

    private function retriever(): TaggedContactsRetriever
    {
        return self::getContainer()->get(TaggedContactsRetriever::class);
    }
}
