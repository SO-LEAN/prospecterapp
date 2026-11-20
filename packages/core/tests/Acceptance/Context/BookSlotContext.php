<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Booking\Application\BookSlot\BookSlot;
use Prospecter\Core\Booking\Application\ConsultMyBookingPage\ConsultMyBookingPage;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

/**
 * A booker books a slot by the public link of the member signed in, without signing in themselves (#179).
 */
final class BookSlotContext implements Context
{
    use SharesTheScenario;

    private const array CHANNELS = ['un appel' => 'call', 'une visio' => 'videoCall'];
    private const string SLOT = '(?<day>\d{2}\/\d{2}\/\d{4}) à (?<time>\d{1,2}h\d{2})';

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

    private function book(string $channel, string $day, string $time, string $phone = '06 12 34 56 78', string $email = 'ada@acme.fr'): void
    {
        $this->application->dispatch(new BookSlot(
            $this->myCode(),
            $this->application->newIdentity(),
            $this->application->newIdentity(),
            $channel,
            $this->slot($day, $time),
            'Ada',
            'Lovelace',
            $phone,
            $email,
            null,
            $this->application->now(),
        ));
    }

    private function expectBooked(): void
    {
        Expect::that(null === $this->application->refusal(), sprintf('The slot was expected to be booked, got: %s', $this->application->refusal()?->getMessage()));
    }

    private function slot(string $day, string $time): \DateTimeImmutable
    {
        return Vocabulary::frenchMoment(sprintf('%s à %s', $day, $time))->setTimezone($this->application->readerClock());
    }

    private function myCode(): string
    {
        return $this->application->consultMyBookingPage(new ConsultMyBookingPage($this->application->signedIn(), $this->application->readerClock()->getName(), $this->application->now()))->code ?? '';
    }
}
