<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\Prospecting\Application\ConsultContactToAttach\ConsultContactToAttach;
use Prospecter\Core\Prospecting\Application\ContactToAttachInList;
use Prospecter\Core\Prospecting\Application\EraseContactLeftUnattached\EraseContactLeftUnattached;
use Prospecter\Core\Prospecting\Application\PlanExchange\PlanExchange;
use Prospecter\Core\Prospecting\Application\ProposeOrganizationToAttach\ProposedOrganization;
use Prospecter\Core\Prospecting\Application\ProposeOrganizationToAttach\ProposeOrganizationToAttach;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\ContactToAttach;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;
use Tests\Core\Double\StateOf;

/**
 * The contacts to attach (ADR 0146): a person who took a meeting with the signed-in member, kept without
 * organization until they attach them, erased 30 days after their last meeting otherwise.
 */
final class ContactToAttachContext implements Context
{
    use SharesTheScenario;

    private string $lastMet = '';
    private ?ProposedOrganization $proposed = null;

    /**
     * Arranged as the booking of a meeting will do it: the person collected without organization, met by the
     * signed-in member, the meeting planned by them.
     */
    #[Given('/^"(?<name>[^"]+)" \((?<email>[^)]+)\) a pris une visio avec moi le (?<day>\S+) à (?<time>\S+)$/')]
    public function aPersonTookAVideoCallWithMe(string $name, string $email, string $day, string $time): void
    {
        $meeting = Vocabulary::frenchMoment(sprintf('%s à %s', $day, $time));
        $holder = $this->scenario->meAsProspector();
        [$firstName, $lastName] = array_pad(explode(' ', $name, 2), 2, null);
        $person = Person::collectToAttach($this->application->readers->readerFrom($holder->id), new PersonId($this->application->newIdentity()), new PersonName($firstName, $lastName), new ContactDetails(new Email($email)), $this->application->now());
        $this->application->persons->save($person);
        $this->scenario->rememberPerson($name, $person->id());
        $this->application->contactsToAttach->save(ContactToAttach::metBy($holder, $person->id(), $meeting, $this->application->now()));
        $this->application->dispatch(new PlanExchange($holder->id->value, $this->application->newIdentity(), $person->id()->value, Channel::VideoCall->value, $meeting, null, null, $this->application->now()));
        Expect::that(null === $this->application->refusal(), sprintf('The meeting was expected to be planned, got: %s', $this->application->refusal()?->getMessage()));
        $this->lastMet = $name;
    }

    #[Then('/^"(?<name>[^"]+)" est un contact à rattacher, effacée? le (?<day>\S+) sans rattachement$/')]
    public function isAContactToAttach(string $name, string $day): void
    {
        $toAttach = $this->contactToAttach($name);
        Expect::that(null !== $toAttach, sprintf('"%s" is no contact to attach', $name));

        Expect::same($day, $this->dayOf($toAttach->erasableFrom));
    }

    #[Then(':name n\'est plus à rattacher')]
    public function isNoLongerToAttach(string $name): void
    {
        Expect::same(null, $this->contactToAttach($name));
    }

    #[Then('la visio avec :contact est gardée pour :organization')]
    public function theVideoCallIsKeptFor(string $contact, string $organization): void
    {
        $kept = array_map(static fn (Exchange $exchange): array => StateOf::the($exchange)['keptFor'], $this->exchangesWith($contact));

        Expect::same([[$this->scenario->organizationNamed($organization)->id()]], $kept);
    }

    #[When('/^je regarde à quelle organisation (?:la|le) rattacher$/')]
    public function iLookWhereToAttach(): void
    {
        $email = (string) $this->scenario->personNamed($this->lastMet)->contactDetails()->email;
        $this->proposed = $this->application->proposeOrganizationToAttach(new ProposeOrganizationToAttach($this->application->signedIn(), $email));
        Expect::that(null === $this->application->refusal(), sprintf('Refused: %s', $this->application->refusal()?->getMessage()));
    }

    #[Then(':organization est proposée')]
    public function theOrganizationIsProposed(string $organization): void
    {
        Expect::same($this->scenario->idOf($organization), $this->proposed?->id);
    }

    #[Then('aucune organisation n\'est proposée')]
    public function noOrganizationIsProposed(): void
    {
        Expect::same(null, $this->proposed);
    }

    #[When('les contacts jamais rattachés sont effacés')]
    public function theContactsLeftUnattachedAreErased(): void
    {
        $this->application->eraseContactsLeftUnattached();
    }

    #[When('l\'effacement de :name est demandé de nouveau')]
    public function itsErasureIsAskedAgain(string $name): void
    {
        $this->application->dispatch(new EraseContactLeftUnattached($this->scenario->personIdOf($name), $this->application->now()));
    }

    #[Then(':name est effacée')]
    public function isErased(string $name): void
    {
        $person = new PersonId($this->scenario->personIdOf($name));

        Expect::same([[], null], [$this->application->persons->ofIds([$person]), $this->application->contactsToAttach->find($person)]);
    }

    #[Then('ses échanges et ses actions à faire sont effacés')]
    public function itsExchangesAndToDosAreErased(): void
    {
        Expect::same([[], []], [$this->exchangesWith($this->lastMet), $this->application->toDos->forContact(new PersonId($this->scenario->personIdOf($this->lastMet)), $this->accountOfMine())]);
    }

    private function contactToAttach(string $name): ?ContactToAttachInList
    {
        return $this->application->consultContactToAttach(new ConsultContactToAttach($this->application->signedIn(), $this->scenario->personIdOf($name)));
    }

    /**
     * @return list<Exchange>
     */
    private function exchangesWith(string $contact): array
    {
        return $this->application->exchanges->withContact(new PersonId($this->scenario->personIdOf($contact)), $this->accountOfMine());
    }

    private function accountOfMine(): AccountId
    {
        return $this->application->prospectors->prospectorFrom(new MemberId($this->application->signedIn()))->accountId;
    }

    private function dayOf(\DateTimeImmutable $moment): string
    {
        return $moment->setTimezone($this->application->readerClock())->format('d/m/Y');
    }
}
