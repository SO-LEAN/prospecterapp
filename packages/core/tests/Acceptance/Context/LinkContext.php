<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\PersonalData\Application\ChangePersonContactDetails\ChangePersonContactDetails;
use Prospecter\Core\Prospecting\Application\ChangeContactDetails\ChangeContactDetails;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\WebPresence;
use Prospecter\Core\Prospecting\Domain\Model\Website;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Designated;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;
use Tests\Core\Double\StateOf;

/**
 * An organization's or a person's links to outside tools, and the references they stand for.
 */
final class LinkContext implements Context
{
    use SharesTheScenario;

    #[Given(':name fait partie de mes organisations avec le lien :address')]
    public function anOrganizationOfMineWithTheLink(string $name, string $address): void
    {
        $organization = $this->scenario->isAnOrganization($name) ? $this->scenario->organizationNamed($name) : $this->anOrganizationOfMine($name);
        $organization->changeContactDetails($organization->contactDetails(), new WebPresence(ExternalReferences::designating([$address], Designated::Organization), StateOf::the($organization)->objectOrNull('website', Website::class)), $this->scenario->managerOf($organization), $this->application->now());
        $this->application->organizations->save($organization);
    }

    #[When('j\'ajoute à :name le lien :address')]
    public function iAddTheLink(string $name, string $address): void
    {
        $this->changeTheLinks($name, [...$this->addressesOf($name), $address]);
    }

    #[When('je retire de :name le lien :address')]
    public function iRemoveTheLink(string $name, string $address): void
    {
        $this->changeTheLinks($name, array_values(array_diff($this->addressesOf($name), [$address])));
    }

    #[Then('/^"([^"]*)" a la référence (LinkedIn entreprise|LinkedIn|Malt|Welcome to the Jungle) "([^"]*)"$/')]
    public function itHasTheReference(string $name, string $source, string $identifier): void
    {
        $this->itHasTheReferenceOfSource($name, Vocabulary::source($source), $identifier);
    }

    #[Then(':name a la référence de source :source et d\'identifiant :identifier')]
    public function itHasTheReferenceOfSource(string $name, string $source, string $identifier): void
    {
        Expect::that(in_array(sprintf('%s#%s', $source, $identifier), array_map(static fn (ExternalReference $reference): string => $reference->key(), $this->referencesOf($name)), true), sprintf('"%s" was expected to have the reference %s "%s"', $name, $source, $identifier));
    }

    #[Then(':name a le lien :address')]
    public function itHasTheLink(string $name, string $address): void
    {
        Expect::that(in_array($address, $this->addressesOf($name), true), sprintf('"%s" was expected to have the link "%s"', $name, $address));
    }

    private function anOrganizationOfMine(string $name): Organization
    {
        $organization = Organization::prospectedBy($this->scenario->myGesture(), new OrganizationId($this->application->newIdentity()), new LegalIdentity($name), new ContactDetails());

        return $this->scenario->save($name, $organization);
    }

    /**
     * @param list<string> $addresses
     */
    private function changeTheLinks(string $name, array $addresses): void
    {
        $this->application->dispatch($this->scenario->isAnOrganization($name) ? $this->changingTheLinksOfTheOrganization($name, $addresses) : $this->changingTheLinksOfThePerson($name, $addresses));
    }

    /**
     * @param list<string> $addresses
     */
    private function changingTheLinksOfTheOrganization(string $name, array $addresses): ChangeContactDetails
    {
        $organization = $this->scenario->organizationNamed($name);
        $contact = $organization->contactDetails();

        return new ChangeContactDetails($this->application->signedIn(), $organization->id()->value, $contact->email?->value, $contact->phoneNumber?->value, $contact->language, $this->application->now(), $addresses, StateOf::the($organization)->objectOrNull('website', Website::class)?->value);
    }

    /**
     * @param list<string> $addresses
     */
    private function changingTheLinksOfThePerson(string $name, array $addresses): ChangePersonContactDetails
    {
        $person = $this->scenario->personNamed($name);
        $contact = $person->contactDetails();

        return new ChangePersonContactDetails($this->application->signedIn(), $person->id()->value, $contact->email?->value, $contact->phoneNumber?->value, $contact->language, $addresses, $this->application->now());
    }

    /**
     * @return list<string>
     */
    private function addressesOf(string $name): array
    {
        return array_map(static fn (ExternalReference $reference): string => $reference->address, $this->referencesOf($name));
    }

    /**
     * @return list<ExternalReference>
     */
    private function referencesOf(string $name): array
    {
        return ($this->scenario->isAnOrganization($name) ? $this->scenario->organizationNamed($name)->references() : $this->scenario->personNamed($name)->references())->all();
    }
}
