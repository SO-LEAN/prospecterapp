<?php

namespace Tests\App\Prospecting;

use App\Prospecting\Persistence\DynamoExchangeRepository;
use App\Prospecting\Persistence\ExchangeKeys;
use App\Shared\Persistence\ConditionFailed;
use App\Shared\Persistence\DynamoUnitOfWork;
use App\Shared\Persistence\Hydrator;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeNotedMeanwhile;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\ContactCorrespondent;
use Prospecter\Core\Prospecting\Domain\Model\Correction;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Erasure;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFingerprint;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\Fingerprinted;
use Prospecter\Core\Prospecting\Domain\Model\ImportedEmail;
use Prospecter\Core\Prospecting\Domain\Model\Noting;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationCorrespondent;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Origin;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * The exchanges kept for an organization and those with a contact, from the items the repository writes next
 * to them (ADR 0074, 0076); their timelines are read by `DynamoTimelineRetriever`.
 */
class DynamoExchangeRepositoryTest extends ApiTestCase
{
    private const string NOW = '2026-10-18 19:00 UTC';

    private Prospector $prospector;
    private PersonId $contact;
    private OrganizationId $acme;
    private OrganizationId $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()), true);
        $this->contact = new PersonId(self::newIdentity());
        $this->acme = new OrganizationId(self::newIdentity());
        $this->group = new OrganizationId(self::newIdentity());
    }

    public function testAnExchangeIsFoundByTheOrganizationsItIsKeptFor(): void
    {
        $exchanges = $this->exchanges();
        $exchange = $this->noted('2026-10-10 09:00 UTC');
        $exchanges->save($exchange);

        $exchange->stopKeepingFor($this->acme, by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $exchanges->save($exchange);

        $this->assertSame([], $exchanges->keptFor($this->acme));
        $this->assertEquals([$exchange->id()], array_map(static fn (Exchange $kept): ExchangeId => $kept->id(), $exchanges->keptFor($this->group)));
        $this->assertEquals([$this->group], Hydrator::stateOf($exchanges->keptFor($this->group)[0])['keptFor']);
    }

    public function testARemovedExchangeIsFoundNowhere(): void
    {
        $exchanges = $this->exchanges();
        $exchange = $this->noted('2026-10-10 09:00 UTC');
        $exchanges->save($exchange);

        $exchanges->remove($exchange);

        $this->assertSame([], $exchanges->keptFor($this->acme));
        $this->assertSame([], $exchanges->keptFor($this->group));
        $this->assertSame([], $exchanges->withContact($this->contact, $this->prospector->accountId));
    }

    private function notedWithTheOrganization(string $occurredAt): Exchange
    {
        $organization = Organization::prospectedBy($this->prospector, $this->acme, new LegalIdentity('ACME'), new ContactDetails(), new \DateTimeImmutable(self::NOW));

        return Exchange::noted(new Gesture($organization->accountId(), $this->prospector->id, new \DateTimeImmutable(self::NOW)), new ExchangeId(self::newIdentity()), new OrganizationCorrespondent($organization->id()), Noting::of(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable($occurredAt)), ExchangeStatus::Done));
    }

    public function testACorrectedExchangeIsReadWithItsCorrection(): void
    {
        $exchanges = $this->exchanges();
        $exchange = $this->noted('2026-10-10 09:00 UTC');
        $exchanges->save($exchange);
        $corrected = new ExchangeFacts(Channel::Email, Direction::Outbound, new \DateTimeImmutable('2026-10-12 10:00 UTC'), 'Objet : mission');

        $exchange->correct($corrected, ExchangeStatus::Done, by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $exchanges->save($exchange);

        $read = $exchanges->get($exchange->id());
        $this->assertEquals($corrected, Hydrator::stateOf($read)['facts']);
        $this->assertEquals(new Correction($this->prospector->id, new \DateTimeImmutable(self::NOW)), Hydrator::stateOf($read)['lastCorrection']);
    }

    public function testTheExchangesWithAContactAreFoundAll(): void
    {
        $exchanges = $this->exchanges();
        $first = $this->noted('2026-10-10 09:00 UTC');
        $second = $this->noted('2026-10-11 09:00 UTC');
        array_map($exchanges->save(...), [$first, $second, $this->notedWithTheOrganization('2026-10-12 09:00 UTC')]);

        $found = $exchanges->withContact($this->contact, $this->prospector->accountId);

        $this->assertEquals([$second->id(), $first->id()], array_map(static fn (Exchange $exchange): ExchangeId => $exchange->id(), $found));
    }

    public function testARedactedExchangeIsReadWithWhoDeclaredIt(): void
    {
        $exchanges = $this->exchanges();
        $exchange = $this->noted('2026-10-10 09:00 UTC');
        $exchange->forgetItsContact(by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $exchanges->save($exchange);

        $exchange->declareConform(by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $exchanges->save($exchange);

        $item = $this->prospectingTable()->item(ExchangeKeys::exchange($exchange->id()->value), ExchangeKeys::EXCHANGE);
        $this->assertSame($this->prospector->id->value, $item['exchange']['redacted']['by']);
        $read = $exchanges->get($exchange->id());
        $this->assertEquals(new Correction($this->prospector->id, new \DateTimeImmutable(self::NOW)), Hydrator::stateOf($read)['madeConform']);
        $this->assertFalse($read->isToMakeConform());
    }

    public function testAnImportedExchangeIsReadWithWhereItComesFromAndItsFingerprintOutlivesIt(): void
    {
        $exchanges = $this->exchanges();
        $imported = Exchange::noted(new Gesture($this->prospector->accountId, $this->prospector->id, new \DateTimeImmutable(self::NOW)), new ExchangeId(self::newIdentity()), new ContactCorrespondent($this->contact, [$this->acme]), Noting::imported(new ExchangeFacts(Channel::Email, Direction::Inbound, new \DateTimeImmutable('2026-10-10 09:00 UTC'), "Votre devis\n\nBonjour"), new ImportedEmail('google', 'f00d')));
        $imported->releaseEvents();
        $exchanges->save($imported);

        $this->assertEquals($imported, $exchanges->get($imported->id()));

        $imported->erase(by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $exchanges->remove($imported);

        $this->assertNotNull($exchanges->fingerprinted($this->prospector->accountId, self::fingerprintOf($imported))?->erasure);
    }

    public function testAnExchangeAnAiNotedIsReadCreatedByAnAi(): void
    {
        $exchanges = $this->exchanges();
        $byAnAI = new Gesture($this->prospector->accountId, $this->prospector->id, new \DateTimeImmutable(self::NOW), Origin::Ai);
        $noted = Exchange::noted($byAnAI, new ExchangeId(self::newIdentity()), new ContactCorrespondent($this->contact, [$this->acme]), Noting::of(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable('2026-10-10 09:00 UTC')), ExchangeStatus::Done));
        $exchanges->save($noted);

        $this->assertSame(Origin::Ai, Hydrator::stateOf($exchanges->get($noted->id()))['origin']);
    }

    public function testAnImportedExchangeIsReadWithTheIdOfItsMessageInTheMailboxThatImportedIt(): void
    {
        $exchanges = $this->exchanges();
        $imported = $this->imported(new ImportedEmail('google', 'f00d', '18c2f4a9e0b1d3c7'));
        $exchanges->save($imported);

        $this->assertSame('18c2f4a9e0b1d3c7', Hydrator::stateOf($exchanges->get($imported->id()))['importedFrom']->providerMessageId);
    }

    public function testAnExchangeImportedBeforeTheIdOfItsMessageWasKeptHasNone(): void
    {
        $exchanges = $this->exchanges();
        $imported = $this->imported();
        $exchanges->save($imported);
        $item = $this->prospectingTable()->item(ExchangeKeys::exchange($imported->id()->value), ExchangeKeys::EXCHANGE);
        unset($item['exchange']['importedFrom']['providerMessageId']);
        $this->prospectingTable()->batch([$item]);

        $this->assertNull(Hydrator::stateOf($exchanges->get($imported->id()))['importedFrom']->providerMessageId);
    }

    public function testANotedExchangeIsFoundByItsFingerprint(): void
    {
        $exchanges = $this->exchanges();
        $exchange = $this->noted('2026-10-10 09:00 UTC');
        $exchanges->save($exchange);

        $this->assertEquals(new Fingerprinted($exchange->id()), $exchanges->fingerprinted($this->prospector->accountId, self::fingerprintOf($exchange)));
    }

    public function testTheRegistrationRefusesAFingerprintNotedMeanwhile(): void
    {
        $this->exchanges()->save($this->noted('2026-10-10 09:00 UTC'));

        $this->expectException(ExchangeNotedMeanwhile::class);

        $this->exchanges()->save($this->noted('2026-10-10 09:00 UTC'));
    }

    public function testTheFingerprintOfAnErasedExchangeKeepsWhoErasedIt(): void
    {
        $exchanges = $this->exchanges();
        $exchange = $this->noted('2026-10-10 09:00 UTC');
        $exchanges->save($exchange);

        $exchange->erase(by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $exchanges->remove($exchange);

        $this->assertEquals(new Fingerprinted($exchange->id(), new Erasure($this->prospector->id, new \DateTimeImmutable(self::NOW))), $exchanges->fingerprinted($this->prospector->accountId, self::fingerprintOf($exchange)));
    }

    public function testTheFingerprintsOfTheExchangesWithAContactAreForgottenWithThem(): void
    {
        $exchanges = $this->exchanges();
        $kept = $this->noted('2026-10-10 09:00 UTC');
        $erased = $this->noted('2026-10-11 09:00 UTC');
        $exchanges->save($kept);
        $exchanges->save($erased);
        $erased->erase(by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $exchanges->remove($erased);

        $exchanges->forgetFingerprintsOf($this->prospector->accountId, $this->contact);

        $this->assertNull($exchanges->fingerprinted($this->prospector->accountId, self::fingerprintOf($kept)));
        $this->assertNull($exchanges->fingerprinted($this->prospector->accountId, self::fingerprintOf($erased)));
    }

    public function testAnExchangeIsReadWithItsFingerprint(): void
    {
        $exchanges = $this->exchanges();
        $exchange = $this->noted('2026-10-10 09:00 UTC');
        $exchange->releaseEvents();
        $exchanges->save($exchange);

        $this->assertEquals($exchange->fingerprint(), $exchanges->get($exchange->id())->fingerprint());
    }

    public function testAnExchangeImportedBeforeTheFingerprintsReadsWithThatOfItsEMail(): void
    {
        $exchanges = $this->exchanges();
        $imported = $this->imported();
        $exchanges->save($imported);
        $this->writtenBeforeTheFingerprints($imported);

        $this->assertEquals($imported->fingerprint(), $exchanges->get($imported->id())->fingerprint());
    }

    public function testAnExchangeImportedBeforeTheFingerprintsWithAContactForgottenSinceHasNone(): void
    {
        $exchanges = $this->exchanges();
        $imported = $this->imported();
        $exchanges->save($imported);
        $imported->forgetItsContact(by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $exchanges->save($imported);
        $this->writtenBeforeTheFingerprints($imported);

        $this->assertNull($exchanges->get($imported->id())->fingerprint());
    }

    public function testTheRebuildOfTheKeysRegistersTheFingerprintOfAnExchangeImportedBeforeThem(): void
    {
        $imported = $this->imported();
        $this->exchanges()->save($imported);
        $this->writtenBeforeTheFingerprints($imported);
        $this->prospectingTable()->batch([], [[ExchangeKeys::fingerprint($imported->accountId()->value, self::fingerprintOf($imported)->value), ExchangeKeys::FINGERPRINT]]);

        self::getContainer()->get(DynamoExchangeRepository::class)->writeTheKeysAgain();

        $this->assertEquals(new Fingerprinted($imported->id()), $this->exchanges()->fingerprinted($this->prospector->accountId, self::fingerprintOf($imported)));
    }

    public function testAnExchangeNeverNotedIsUnknown(): void
    {
        $this->expectException(UnknownExchange::class);

        $this->exchanges()->get(new ExchangeId(self::newIdentity()));
    }

    public function testAnExchangeChangedSinceItWasReadIsNotSavedOverTheChange(): void
    {
        $exchange = $this->noted('2026-10-10 09:00 UTC');
        $this->exchanges()->save($exchange);
        $unitOfWork = self::getContainer()->get(DynamoUnitOfWork::class);
        $unitOfWork->begin();
        $read = $this->exchanges()->get($exchange->id());
        $theirs = $this->anotherRequestCorrects($exchange->id(), 'Objet : mission');
        $read->correct(new ExchangeFacts(Channel::Call, Direction::Outbound, new \DateTimeImmutable('2026-10-12 10:00 UTC')), ExchangeStatus::Done, by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $this->exchanges()->save($read);

        try {
            $unitOfWork->commit();
            $this->fail('The save was expected to be cancelled');
        } catch (ConditionFailed) {
            $unitOfWork->discard();
        }

        $this->assertEquals($theirs, Hydrator::stateOf($this->exchanges()->get($exchange->id()))['facts']);
    }

    public function testAnExchangeChangedSinceItWasReadIsNotRemovedOverTheChange(): void
    {
        $exchange = $this->noted('2026-10-10 09:00 UTC');
        $this->exchanges()->save($exchange);
        $unitOfWork = self::getContainer()->get(DynamoUnitOfWork::class);
        $unitOfWork->begin();
        $read = $this->exchanges()->get($exchange->id());
        $theirs = $this->anotherRequestCorrects($exchange->id(), 'Objet : mission');
        $this->exchanges()->remove($read);

        try {
            $unitOfWork->commit();
            $this->fail('The removal was expected to be cancelled');
        } catch (ConditionFailed) {
            $unitOfWork->discard();
        }

        $this->assertEquals($theirs, Hydrator::stateOf($this->exchanges()->get($exchange->id()))['facts']);
    }

    private function anotherRequestCorrects(ExchangeId $exchange, string $text): ExchangeFacts
    {
        $exchanges = new DynamoExchangeRepository($this->asAnotherRequest($this->prospectingTable()));
        $correcting = $exchanges->get($exchange);
        $corrected = new ExchangeFacts(Channel::Email, Direction::Outbound, new \DateTimeImmutable('2026-10-12 10:00 UTC'), $text);
        $correcting->correct($corrected, ExchangeStatus::Done, by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $exchanges->save($correcting);

        return $corrected;
    }

    private function noted(string $occurredAt): Exchange
    {
        return Exchange::noted(new Gesture($this->prospector->accountId, $this->prospector->id, new \DateTimeImmutable(self::NOW)), new ExchangeId(self::newIdentity()), new ContactCorrespondent($this->contact, [$this->acme, $this->group]), Noting::of(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable($occurredAt), 'Rappeler lundi'), ExchangeStatus::Done));
    }

    private function imported(ImportedEmail $email = new ImportedEmail('google', 'f00d')): Exchange
    {
        $imported = Exchange::noted(new Gesture($this->prospector->accountId, $this->prospector->id, new \DateTimeImmutable(self::NOW)), new ExchangeId(self::newIdentity()), new ContactCorrespondent($this->contact, [$this->acme]), Noting::imported(new ExchangeFacts(Channel::Email, Direction::Inbound, new \DateTimeImmutable('2026-10-10 09:00 UTC'), "Votre devis\n\nBonjour"), $email));
        $imported->releaseEvents();

        return $imported;
    }

    /**
     * Its item as written before the exchanges kept their fingerprint.
     */
    private function writtenBeforeTheFingerprints(Exchange $exchange): void
    {
        $item = $this->prospectingTable()->item(ExchangeKeys::exchange($exchange->id()->value), ExchangeKeys::EXCHANGE);
        unset($item['exchange']['fingerprint']);
        $this->prospectingTable()->batch([$item]);
    }

    private function prospectingTable(): Table
    {
        $table = self::getContainer()->get('app.table.prospecting');
        $this->assertInstanceOf(Table::class, $table);

        return $table;
    }

    private static function fingerprintOf(Exchange $exchange): ExchangeFingerprint
    {
        return $exchange->fingerprint() ?? throw new \LogicException('A noted exchange has a fingerprint');
    }

    private function exchanges(): ExchangeRepository
    {
        return self::getContainer()->get(ExchangeRepository::class);
    }
}
