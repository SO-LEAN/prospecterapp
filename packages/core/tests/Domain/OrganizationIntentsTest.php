<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationIntent;
use Prospecter\Core\Prospecting\Domain\OrphanContacts;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Address;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;
use Tests\Core\Double\InMemoryDatabase;
use Tests\Core\Double\InMemoryOrganizationRepository;

/**
 * The API shows the link of an intention its state offers, and the aggregate refuses a call it does not:
 * an intention must be refused exactly when the organization does not offer it.
 */
class OrganizationIntentsTest extends TestCase
{
    private const string MINE = '0190a1c0-0000-7000-8000-000000000001';
    private const string PROSPECT = '0190a1c0-0000-7000-8000-000000000003';
    private const string HOLDING = '0190a1c0-0000-7000-8000-000000000004';
    private const string CONTACT = '0190a1c0-0000-7000-8000-000000000005';
    private const string NEW_CONTACT = '0190a1c0-0000-7000-8000-000000000006';
    private const string FULL = 'with logo, holding and a contact';
    private const string AUTHOR = '0190a1c0-0000-7000-8000-0000000000aa';

    #[DataProvider('situations')]
    public function testAnIntentionIsRefusedExactlyWhenItIsNotOffered(OrganizationIntent $intent, string $state): void
    {
        $organization = $this->prospect($state);
        $organization->releaseEvents();
        $offered = in_array($intent, $organization->possibleIntents(), true);

        $accepted = $this->isAccepted($intent, $organization, new MemberId(self::AUTHOR));

        $this->assertSame($offered, $accepted, sprintf('%s offered: %s', $intent->name, var_export($offered, true)));
        $this->assertSame($accepted, [] !== $organization->releaseEvents(), 'an accepted intention records what happened, a refused one nothing');
    }

    /**
     * @return iterable<string, array{OrganizationIntent, string}>
     */
    public static function situations(): iterable
    {
        foreach (OrganizationIntent::cases() as $intent) {
            foreach (['bare', self::FULL] as $state) {
                yield sprintf('%s, %s', $intent->name, $state) => [$intent, $state];
            }
        }
    }

    private function isAccepted(OrganizationIntent $intent, Organization $organization, MemberId $by): bool
    {
        try {
            $this->carryOut($intent, $organization, $by);
        } catch (NotFound|BusinessRuleViolation) {
            return false;
        }

        return true;
    }

    private function carryOut(OrganizationIntent $intent, Organization $organization, MemberId $by): void
    {
        match ($intent) {
            OrganizationIntent::ReviseLegalIdentity => $organization->reviseLegalIdentity(new LegalIdentity('ACME SAS'), $by, new \DateTimeImmutable()),
            OrganizationIntent::ChangeContactDetails => $organization->changeContactDetails(new ContactDetails(phoneNumber: PhoneNumber::of('03 88 00 00 00')), $by, new \DateTimeImmutable()),
            OrganizationIntent::Relocate => $organization->relocateTo(new Address(city: 'Colmar'), null, $by, new \DateTimeImmutable()),
            OrganizationIntent::WriteObservations => $organization->writeObservations('Rappeler lundi', $by, new \DateTimeImmutable()),
            OrganizationIntent::AttachToHolding => $organization->attachToHolding($this->holding(), $by, new \DateTimeImmutable()),
            OrganizationIntent::DetachFromHolding => $organization->detachFromHolding($by, new \DateTimeImmutable()),
            OrganizationIntent::ChangeLogo => $organization->changeLogo($this->logo(), $by, new \DateTimeImmutable()),
            OrganizationIntent::RemoveLogo => $organization->removeLogo($by, new \DateTimeImmutable()),
            OrganizationIntent::AddContact => $organization->addContact(new PersonId(self::NEW_CONTACT), $by, new \DateTimeImmutable()),
            OrganizationIntent::RemoveContact => $organization->removeContact(new PersonId(self::CONTACT), $by, new \DateTimeImmutable(), $this->orphans()),
            OrganizationIntent::StopProspecting => $organization->stopBeingProspected($by, new \DateTimeImmutable(), $this->orphans()),
        };
    }

    private function prospect(string $state): Organization
    {
        $prospect = Organization::prospectedBy($this->prospector(), new OrganizationId(self::PROSPECT), new LegalIdentity('ACME'), new ContactDetails(), new \DateTimeImmutable());

        if (self::FULL === $state) {
            $prospect->changeLogo($this->logo(), $this->prospector()->id, new \DateTimeImmutable());
            $prospect->attachToHolding($this->holding(), $this->prospector()->id, new \DateTimeImmutable());
            $prospect->addContact(new PersonId(self::CONTACT), $this->prospector()->id, new \DateTimeImmutable());
        }

        return $prospect;
    }

    /**
     * No other organization: every contact the organization lets go is left alone.
     */
    private function orphans(): OrphanContacts
    {
        return new OrphanContacts(new InMemoryOrganizationRepository(new InMemoryDatabase()));
    }

    private function holding(): Organization
    {
        return Organization::prospectedBy($this->prospector(), new OrganizationId(self::HOLDING), new LegalIdentity('Groupe ACME'), new ContactDetails(), new \DateTimeImmutable());
    }

    private function logo(): File
    {
        return new File('https://storage.test/acme.png', 'png', 1024);
    }

    private function prospector(): Prospector
    {
        return new Prospector(new MemberId(self::AUTHOR), new AccountId(self::MINE));
    }
}
