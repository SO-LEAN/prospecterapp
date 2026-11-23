<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Booking\Application\BookSlot\BookSlot;
use Prospecter\Core\Booking\Application\ConsultMyBookingPage\ConsultMyBookingPage;
use Prospecter\Core\Booking\Contract\Service\ConsultAppointments\AppointmentResponse;
use Prospecter\Core\Booking\Contract\Service\ConsultAppointments\ConsultAppointments;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\Prospecting\Application\AddContact\AddContact;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ConsultExchange;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\EraseExchange\EraseExchange;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;
use Tests\Core\Double\StateOf;

/**
 * A booker books a slot through the signed-in member's public link, without signing in themselves (#179).
 */
final class BookSlotContext implements Context
{
    use SharesTheScenario;

    private const array CHANNELS = ['un appel' => 'call', 'une visio' => 'videoCall'];
    private const string SLOT = '(?<day>\d{2}\/\d{2}\/\d{4}) (?:à|de) (?<time>\d{1,2}h\d{2})';

    private ?string $appointment = null;
    /** The id the booker gets when nobody in the account matches them. */
    private ?string $booker = null;
    private ?ExchangeDetail $detail = null;
    /** @var list<AppointmentResponse> */
    private array $answered = [];

    #[When('/^on réserve (?<channel>un appel|une visio) par mon lien public le '.self::SLOT.' :$/u')]
    public function someoneBooksAs(string $channel, string $day, string $time, TableNode $table): void
    {
        $booker = Vocabulary::rows($table);
        $this->book(self::CHANNELS[$channel], $day, $time, $booker['téléphone'], $booker['e-mail'], $booker['prénom'], $booker['nom'], $booker['objet'] ?? null);
    }

    #[When('/^on réserve (?<channel>un appel|une visio) par mon lien public le '.self::SLOT.' avec l\'e-mail "(?<email>[^"]+)"$/u')]
    public function someoneBooksWithTheEmail(string $channel, string $day, string $time, string $email): void
    {
        $this->book(self::CHANNELS[$channel], $day, $time, email: $email);
    }

    #[When('/^on réserve (?<channel>un appel|une visio) par mon lien public le '.self::SLOT.' avec le téléphone "(?<phone>[^"]+)" et l\'e-mail "(?<email>[^"]+)"$/u')]
    public function someoneBooksWithThePhoneAndTheEmail(string $channel, string $day, string $time, string $phone, string $email): void
    {
        $this->book(self::CHANNELS[$channel], $day, $time, $phone, $email);
    }

    #[When('/^on réserve (?<channel>un appel|une visio) par mon lien public le '.self::SLOT.' sans téléphone$/u')]
    public function someoneBooksWithoutPhone(string $channel, string $day, string $time): void
    {
        $this->book(self::CHANNELS[$channel], $day, $time, phone: '');
    }

    #[When('/^on réserve (?<channel>un appel|une visio) par mon lien public le '.self::SLOT.'$/u')]
    public function someoneBooks(string $channel, string $day, string $time): void
    {
        $this->book(self::CHANNELS[$channel], $day, $time);
    }

    #[Given('/^on a réservé (?<channel>un appel|une visio) par mon lien public le '.self::SLOT.'$/u')]
    public function someoneBooked(string $channel, string $day, string $time): void
    {
        $this->someoneBooks($channel, $day, $time);
        $this->expectBooked();
    }

    #[Given('/^quelqu\'un vient de réserver par mon lien public le '.self::SLOT.'$/u')]
    public function someoneJustBooked(string $day, string $time): void
    {
        $this->book('call', $day, $time, email: 'quelqu.un@exemple.fr');
        $this->expectBooked();
    }

    #[Given('/^on a réservé par mon lien public avec "(?<email>[^"]+)" le '.self::SLOT.'$/u')]
    public function someoneBookedWith(string $email, string $day, string $time): void
    {
        $this->someoneBooksWith($email, $day, $time);
        $this->expectBooked();
    }

    #[When('/^on réserve par mon lien public avec "(?<email>[^"]+)" le '.self::SLOT.'$/u')]
    public function someoneBooksWith(string $email, string $day, string $time): void
    {
        $this->book('call', $day, $time, email: $email);
    }

    #[Then('/^on est prévenu du rendez-vous du '.self::SLOT.'$/u')]
    public function theBookerIsToldOfTheirAppointment(string $day, string $time): void
    {
        Expect::same(['appointment' => $this->slot($day, $time)->format(\DATE_ATOM)], $this->application->refusal()?->context);
    }

    #[Then('/^j\'ai (?<channel>un appel|une visio) planifiée? le '.self::SLOT.' à (?<end>\d{1,2}h\d{2}) avec "(?<contact>[^"]+)", au texte "(?<text>[^"]+)"$/u')]
    public function iHaveAPlannedExchange(string $channel, string $day, string $time, string $end, string $contact, string $text): void
    {
        $exchange = StateOf::the($this->bookedExchange());
        $facts = $exchange->object('facts', ExchangeFacts::class);

        Expect::same(
            [self::CHANNELS[$channel], 'planned', $this->slot($day, $time)->format(\DATE_ATOM), $this->slot($day, $end)->format(\DATE_ATOM), $contact, $text],
            [$facts->channel->value, $exchange->object('status', ExchangeStatus::class)->value, $this->onMyClock($facts->occurredAt), $this->onMyClock($facts->occurredAt->modify(sprintf('+%d minutes', $facts->duration?->minutes))), $this->nameOf($exchange->objectOrNull('contact', ContactId::class)), $facts->text],
        );
    }

    #[Then('/^j\'ai (?<channel>un appel|une visio) planifiée? avec "(?<contact>[^"]+)" de "(?<organization>[^"]+)"$/u')]
    public function iHaveAPlannedExchangeWithAContactOf(string $channel, string $contact, string $organization): void
    {
        $exchange = StateOf::the($this->bookedExchange());

        Expect::same(
            [self::CHANNELS[$channel], $this->scenario->personIdOf($contact), [$this->scenario->idOf($organization)]],
            [$exchange->object('facts', ExchangeFacts::class)->channel->value, $exchange->objectOrNull('contact', ContactId::class)?->value, array_map(strval(...), $exchange->listOf('keptFor', OrganizationId::class))],
        );
    }

    #[Then('/^"(?<name>[^"]+)" est un contact à rattacher, au (?<phone>[^a-z]+) et à (?<email>\S+)$/u')]
    public function isAContactToAttachReachedAt(string $name, string $phone, string $email): void
    {
        $contact = StateOf::the($this->bookedExchange())->objectOrNull('contact', ContactId::class) ?? throw new \LogicException('The booked exchange has no contact');
        $details = $this->application->persons->get(new PersonId($contact->value))->contactDetails();

        Expect::same(
            [$name, PhoneNumber::of(trim($phone))->value, $email, true],
            [$this->nameOf($contact), $details->phoneNumber?->value, (string) $details->email, null !== $this->application->contactsToAttach->find($contact)],
        );
    }

    #[Then('je détiens :name à rattacher, avec la visio réservée')]
    public function iHoldThemToAttachWithTheBookedVideoCall(string $name): void
    {
        $contact = new ContactId($this->scenario->personIdOf($name));

        Expect::same(
            [true, $contact->value],
            [true === $this->application->contactsToAttach->find($contact)?->isHeldBy(new MemberId($this->application->signedIn())), StateOf::the($this->bookedExchange())->objectOrNull('contact', ContactId::class)?->value],
        );
    }

    #[Then('/^la visio du (?<day>\d{2}\/\d{2}\/\d{4}) avec "(?<contact>[^"]+)" reste à "(?<member>[^"]+)"$/u')]
    public function theVideoCallStaysWith(string $day, string $contact, string $member): void
    {
        $contactId = new ContactId($this->scenario->personIdOf($contact));
        $accountId = $this->application->prospectors->prospectorFrom(new MemberId($this->application->signedIn()))->accountId;
        $thatDay = array_values(array_filter($this->application->exchanges->withContact($contactId, $accountId), fn (Exchange $exchange): bool => str_starts_with($this->onMyClock(StateOf::the($exchange)->object('facts', ExchangeFacts::class)->occurredAt), Vocabulary::frenchMoment(sprintf('%s à 0h00', $day))->format('Y-m-d'))));

        Expect::same([$this->scenario->memberIdOf($member)->value], array_map(static fn (Exchange $exchange): string => StateOf::the($exchange)->object('createdBy', MemberId::class)->value, $thatDay));
    }

    #[Then('aucun contact n\'est créé')]
    public function noContactIsCreated(): void
    {
        Expect::same(1, count(array_filter($this->application->database->persons(), static fn (Person $person): bool => Purpose::Prospecting === $person->purpose())));
    }

    #[When('un autre contexte demande à Booking ce rendez-vous et un rendez-vous inconnu')]
    public function anotherContextAsksBooking(): void
    {
        $this->answered = $this->application->consultAppointments(new ConsultAppointments([$this->appointment ?? '', $this->application->newIdentity()]));
    }

    #[Then('/^Booking répond (?<channel>un appel|une visio) avec moi le '.self::SLOT.' pour (?<minutes>\d+) minutes, réservé par "(?<firstName>\S+) (?<lastName>[^"]+)" au (?<phone>\S+) et à (?<email>\S+)$/u')]
    public function bookingAnswers(string $channel, string $day, string $time, int $minutes, string $firstName, string $lastName, string $phone, string $email): void
    {
        Expect::same(
            [[$this->appointment, $this->application->signedIn(), $this->slot($day, $time)->format(\DATE_ATOM), $minutes, self::CHANNELS[$channel], $firstName, $lastName, $phone, $email, null]],
            array_map(static fn (AppointmentResponse $answer): array => [$answer->appointmentId, $answer->hostId, $answer->startsAt->format(\DATE_ATOM), $answer->durationInMinutes, $answer->channel, $answer->firstName, $answer->lastName, $answer->phone, $answer->email, $answer->subject], $this->answered),
        );
    }

    #[When('je consulte l\'échange de cette réservation')]
    public function iConsultTheExchangeOfThisBooking(): void
    {
        $this->detail = $this->application->consultExchange(new ConsultExchange($this->application->signedIn(), $this->appointment ?? '', $this->application->now()));
    }

    #[When('je supprime l\'échange de cette réservation')]
    public function iEraseTheExchangeOfThisBooking(): void
    {
        $this->application->dispatch(new EraseExchange($this->application->signedIn(), $this->appointment ?? '', $this->application->now()));
    }

    #[When('je rattache à :organization qui a réservé')]
    public function iAttachTheBookerTo(string $organization): void
    {
        $this->application->dispatch(new AddContact($this->application->signedIn(), $this->scenario->idOf($organization), $this->booker ?? '', $this->application->now()));
    }

    #[Then('son détail dit qu\'on l\'a réservé par mon lien public')]
    public function itsDetailSaysItWasBookedByMyPublicLink(): void
    {
        Expect::same(true, $this->detail?->bookedByLink);
    }

    private function book(string $channel, string $day, string $time, string $phone = '06 12 34 56 78', string $email = 'ada@acme.fr', string $firstName = 'Ada', string $lastName = 'Lovelace', ?string $subject = null): void
    {
        $this->appointment = $this->application->newIdentity();
        $this->booker = $this->application->newIdentity();
        $this->application->dispatch(new BookSlot(
            $this->myCode(),
            $this->appointment,
            $this->booker,
            $channel,
            $this->slot($day, $time),
            $firstName,
            $lastName,
            $phone,
            $email,
            $subject,
            $this->application->now(),
        ));
        $this->rememberWhatWasBooked(sprintf('%s %s', $firstName, $lastName));
    }

    /**
     * The scenarios' « Cette visio » is the booked exchange. Its booker is named as they gave their name.
     */
    private function rememberWhatWasBooked(string $booker): void
    {
        $exchange = $this->application->database->exchange($this->appointment ?? '');
        if (null === $exchange) {
            return;
        }

        $this->scenario->rememberTheExchange((string) $exchange->id());
        $contact = StateOf::the($exchange)->objectOrNull('contact', ContactId::class);
        if (null !== $contact) {
            $this->scenario->rememberPerson($booker, new PersonId($contact->value));
        }
    }

    private function expectBooked(): void
    {
        Expect::that(null === $this->application->refusal(), sprintf('The slot was expected to be booked, got: %s', $this->application->refusal()?->getMessage()));
    }

    /**
     * The exchange that a booking becomes takes the appointment's id.
     */
    private function bookedExchange(): Exchange
    {
        return $this->application->exchanges->get(new ExchangeId($this->appointment ?? throw new \LogicException('Nobody booked')));
    }

    private function nameOf(?ContactId $contact): string
    {
        $name = null === $contact ? null : $this->application->persons->get(new PersonId($contact->value))->name();

        return trim(sprintf('%s %s', $name?->firstName, $name?->lastName));
    }

    private function slot(string $day, string $time): \DateTimeImmutable
    {
        return Vocabulary::frenchMoment(sprintf('%s à %s', $day, $time))->setTimezone($this->application->readerClock());
    }

    private function onMyClock(\DateTimeImmutable $moment): string
    {
        return $moment->setTimezone($this->application->readerClock())->format(\DATE_ATOM);
    }

    private function myCode(): string
    {
        return $this->application->consultMyBookingPage(new ConsultMyBookingPage($this->application->signedIn(), $this->application->readerClock()->getName(), $this->application->now()))->code ?? '';
    }
}
