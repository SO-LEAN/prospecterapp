<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Application\RecognizeCallingContact\RecognizeCallingContact;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\RecognizeCallingOrganization\RecognizeCallingOrganization;
use Tests\Core\Acceptance\Expect;

/**
 * A call comes in: the organizations I prospect and my contacts having its number, asked to each context.
 */
final class CallerContext implements Context
{
    use SharesTheScenario;

    /** @var list<string>|null */
    private ?array $organizations = null;
    /** @var list<string>|null */
    private ?array $contacts = null;

    #[When('un appel arrive du :phoneNumber')]
    public function aCallComesFrom(string $phoneNumber): void
    {
        $organizations = $this->application->recognizeACallingOrganization(new RecognizeCallingOrganization($this->application->signedIn(), $phoneNumber));
        $this->organizations = null === $organizations ? null : array_map(static fn (OrganizationSheet $organization): string => $organization->fullName, $organizations);

        if (null !== $organizations) {
            $contacts = $this->application->recognizeACallingContact(new RecognizeCallingContact($this->application->signedIn(), $phoneNumber));
            $this->contacts = null === $contacts ? null : array_map(static fn (PersonCard $contact): string => trim($contact->firstName.' '.$contact->lastName), $contacts);
        }
    }

    #[Then('l\'appel vient de l\'organisation :name')]
    public function theCallComesFromTheOrganization(string $name): void
    {
        Expect::same([$name], $this->organizations);
        Expect::same([], $this->contacts);
    }

    #[Then('l\'appel vient du contact :name')]
    public function theCallComesFromTheContact(string $name): void
    {
        Expect::same([], $this->organizations);
        Expect::same([$name], $this->contacts);
    }

    #[Then('personne n\'est reconnu')]
    public function nobodyIsRecognized(): void
    {
        Expect::same([], $this->organizations);
        Expect::same([], $this->contacts);
    }
}
