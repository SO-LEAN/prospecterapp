<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Domain\Organization\Address;
use Prospecter\Core\Domain\Organization\LegalIdentity;
use Prospecter\Core\Domain\Organization\Organization;
use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\Organization\OrganizationIntent;
use Prospecter\Core\Domain\Shared\BusinessRuleViolation;
use Prospecter\Core\Domain\Shared\ContactDetails;
use Prospecter\Core\Domain\Shared\File;
use Prospecter\Core\Domain\Shared\NotFound;
use Prospecter\Core\Domain\User\Role;
use Prospecter\Core\Domain\User\User;
use Prospecter\Core\Domain\User\UserId;

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
    public function testAnIntentionIsRefusedExactlyWhenItIsNotOffered(OrganizationIntent $intent, string $state, string $userOrganization): void
    {
        $user = $this->someoneOf($userOrganization);
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

    private function isAccepted(OrganizationIntent $intent, Organization $organization, User $by): bool
    {
        try {
            $this->carryOut($intent, $organization, $by);
        } catch (NotFound|BusinessRuleViolation) {
            return false;
        }

        return true;
    }

    private function carryOut(OrganizationIntent $intent, Organization $organization, User $by): void
    {
        match ($intent) {
            OrganizationIntent::ReviseLegalIdentity => $organization->reviseLegalIdentity(new LegalIdentity('ACME SAS'), $by),
            OrganizationIntent::ChangeContactDetails => $organization->changeContactDetails(new ContactDetails(phoneNumber: '0388000000'), $by),
            OrganizationIntent::Relocate => $organization->relocateTo(new Address(city: 'Colmar'), null, $by),
            OrganizationIntent::WriteObservations => $organization->writeObservations('Rappeler lundi', $by),
            OrganizationIntent::AttachToHolding => $organization->attachToHolding($this->holding(), $by),
            OrganizationIntent::DetachFromHolding => $organization->detachFromHolding($by),
            OrganizationIntent::ChangeLogo => $organization->changeLogo($this->logo(), $by),
            OrganizationIntent::RemoveLogo => $organization->removeLogo($by),
        };
    }

    private function prospect(string $state): Organization
    {
        $mine = $this->someoneOf(self::MINE);
        $prospect = Organization::prospectedBy($mine, new OrganizationId(self::PROSPECT), new LegalIdentity('ACME'), new ContactDetails());

        if ('with logo and holding' === $state) {
            $prospect->changeLogo($this->logo(), $mine);
            $prospect->attachToHolding($this->holding(), $mine);
        }

        return $prospect;
    }

    private function holding(): Organization
    {
        return Organization::prospectedBy($this->someoneOf(self::MINE), new OrganizationId(self::HOLDING), new LegalIdentity('Groupe ACME'), new ContactDetails());
    }

    private function logo(): File
    {
        return new File('https://storage.test/acme.png', 'png', 1024);
    }

    private function someoneOf(string $organization): User
    {
        return User::register(new UserId('0190a1c0-0000-7000-8000-0000000000aa'), new OrganizationId($organization), 'someone', Role::Prospector);
    }
}
