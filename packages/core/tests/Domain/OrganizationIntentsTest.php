<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Address;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\Prospecting\Domain\OrganizationIntent;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The front shows an action when its link is there, the API refuses a call when it is not: an intention
 * must be refused exactly when the organization does not offer it.
 */
class OrganizationIntentsTest extends TestCase
{
    private const string MINE = '0190a1c0-0000-7000-8000-000000000001';
    private const string THEIRS = '0190a1c0-0000-7000-8000-000000000002';
    private const string PROSPECT = '0190a1c0-0000-7000-8000-000000000003';
    private const string HOLDING = '0190a1c0-0000-7000-8000-000000000004';

    #[DataProvider('situations')]
    public function testAnIntentionIsRefusedExactlyWhenItIsNotOffered(OrganizationIntent $intent, string $state, string $userAccount): void
    {
        $user = $this->someoneOf($userAccount);
        $organization = $this->prospect($state);
        $offered = in_array($intent, $organization->possibleIntentsOf($user), true);

        $this->assertSame($offered, $this->isAccepted($intent, $organization, $user), sprintf('%s offered: %s', $intent->name, var_export($offered, true)));
    }

    /**
     * @return iterable<string, array{OrganizationIntent, string, string}>
     */
    public static function situations(): iterable
    {
        foreach (OrganizationIntent::cases() as $intent) {
            foreach (['bare', 'with logo and holding'] as $state) {
                foreach (['mine' => self::MINE, 'theirs' => self::THEIRS] as $user => $organization) {
                    yield sprintf('%s, %s, by %s', $intent->name, $state, $user) => [$intent, $state, $organization];
                }
            }
        }
    }

    private function isAccepted(OrganizationIntent $intent, Organization $organization, Prospector $by): bool
    {
        try {
            $this->carryOut($intent, $organization, $by);
        } catch (NotFound|BusinessRuleViolation) {
            return false;
        }

        return true;
    }

    private function carryOut(OrganizationIntent $intent, Organization $organization, Prospector $by): void
    {
        match ($intent) {
            OrganizationIntent::ReviseLegalIdentity => $organization->reviseLegalIdentity(new LegalIdentity('ACME SAS'), $by, new \DateTimeImmutable()),
            OrganizationIntent::ChangeContactDetails => $organization->changeContactDetails(new ContactDetails(phoneNumber: '0388000000'), $by, new \DateTimeImmutable()),
            OrganizationIntent::Relocate => $organization->relocateTo(new Address(city: 'Colmar'), null, $by, new \DateTimeImmutable()),
            OrganizationIntent::WriteObservations => $organization->writeObservations('Rappeler lundi', $by, new \DateTimeImmutable()),
            OrganizationIntent::AttachToHolding => $organization->attachToHolding($this->holding(), $by, new \DateTimeImmutable()),
            OrganizationIntent::DetachFromHolding => $organization->detachFromHolding($by, new \DateTimeImmutable()),
            OrganizationIntent::ChangeLogo => $organization->changeLogo($this->logo(), $by, new \DateTimeImmutable()),
            OrganizationIntent::RemoveLogo => $organization->removeLogo($by, new \DateTimeImmutable()),
        };
    }

    private function prospect(string $state): Organization
    {
        $mine = $this->someoneOf(self::MINE);
        $prospect = Organization::prospectedBy($mine, new OrganizationId(self::PROSPECT), new LegalIdentity('ACME'), new ContactDetails(), new \DateTimeImmutable());

        if ('with logo and holding' === $state) {
            $prospect->changeLogo($this->logo(), $mine, new \DateTimeImmutable());
            $prospect->attachToHolding($this->holding(), $mine, new \DateTimeImmutable());
        }

        return $prospect;
    }

    private function holding(): Organization
    {
        return Organization::prospectedBy($this->someoneOf(self::MINE), new OrganizationId(self::HOLDING), new LegalIdentity('Groupe ACME'), new ContactDetails(), new \DateTimeImmutable());
    }

    private function logo(): File
    {
        return new File('https://storage.test/acme.png', 'png', 1024);
    }

    private function someoneOf(string $account): Prospector
    {
        return new Prospector(new MemberId('0190a1c0-0000-7000-8000-0000000000aa'), new AccountId($account));
    }
}
