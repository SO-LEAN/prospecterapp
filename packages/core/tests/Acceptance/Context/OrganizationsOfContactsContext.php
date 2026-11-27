<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Prospecting\Application\Contact\ConsultOrganizationsOfContacts\ConsultOrganizationsOfContacts;
use Prospecter\Core\Prospecting\Application\Contact\ConsultOrganizationsOfContacts\OrganizationOfContact;
use Tests\Core\Acceptance\Expect;

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
