<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Prospecting\Application\Contact\ConsultOrganizationsOfContacts\ConsultOrganizationsOfContacts;
use Prospecter\Core\Prospecting\Application\Contact\ConsultOrganizationsOfContacts\OrganizationOfContact;
use Prospecter\Core\Prospecting\Application\Contact\ListOrganizationContacts\ListOrganizationContacts;
use Prospecter\Core\Prospecting\Application\Contact\ListOrganizationContacts\OrganizationContacts;
use Prospecter\Core\Prospecting\Application\PersonDescription;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

/**
 * The organizations of several contacts at once, as a list of contacts names them.
 */
final class OrganizationsOfContactsContext implements Context
{
    use SharesTheScenario;

    /** @var array<string, string> the contacts asked about, their names by their ids */
    private array $asked = [];

    /** @var array<string, list<OrganizationOfContact>> */
    private array $organizations = [];

    private ?OrganizationContacts $contactsOfTheOrganization = null;
    private string $askedOrganization = '';

    #[When('je consulte les contacts de :name')]
    public function iConsultTheContactsOf(string $name): void
    {
        $this->contactsOfTheOrganization = $this->application->listOrganizationContacts(new ListOrganizationContacts($this->application->signedIn(), $this->scenario->idOf($name)));
    }

    #[When('je consulte les contacts d\'une organisation inconnue')]
    public function iConsultTheContactsOfAnUnknownOrganization(): void
    {
        $this->askedOrganization = $this->application->newIdentity();
        $this->contactsOfTheOrganization = $this->application->listOrganizationContacts(new ListOrganizationContacts($this->application->signedIn(), $this->askedOrganization));
    }

    #[Then('le refus nomme l\'organisation demandée')]
    public function theRefusalNamesTheOrganizationAsked(): void
    {
        Expect::same(sprintf('Organization %s not found', $this->askedOrganization), $this->application->refusal()?->getMessage());
    }

    #[Then('ses contacts sont :')]
    public function itsContactsAre(TableNode $expected): void
    {
        $contacts = Expect::present($this->contactsOfTheOrganization, sprintf('The contacts were expected, got: %s', $this->application->refusal()?->getMessage()));

        Expect::same($expected->getColumnsHash(), array_map(static fn (PersonDescription $contact): array => [
            'contact' => trim(sprintf('%s %s', $contact->firstName, $contact->lastName)),
            'masqué' => Vocabulary::describeYesOrNo($contact->masked),
            'fonction' => (string) $contact->jobTitle,
            'e-mail' => (string) $contact->email,
        ], $contacts->contacts));
    }

    #[When('je consulte les organisations de mes contacts :')]
    public function iConsultTheOrganizationsOfMyContacts(TableNode $contacts): void
    {
        $this->asked = array_combine(array_map($this->scenario->personIdOf(...), $contacts->getColumn(0)), $contacts->getColumn(0));
        $this->organizations = $this->application->consultOrganizationsOfContacts(new ConsultOrganizationsOfContacts($this->application->signedIn(), array_keys($this->asked))) ?? [];
    }

    #[Then('je vois les organisations de mes contacts :')]
    public function iSeeTheOrganizationsOfMyContacts(TableNode $expected): void
    {
        $seen = array_map(fn (string $id, array $organizations): array => [
            'contact' => $this->asked[$id] ?? $id,
            'organisations' => implode(', ', array_map(static fn (OrganizationOfContact $organization): string => $organization->fullName, $organizations)),
        ], array_keys($this->organizations), $this->organizations);

        Expect::same($expected->getColumnsHash(), $seen);
    }
}
