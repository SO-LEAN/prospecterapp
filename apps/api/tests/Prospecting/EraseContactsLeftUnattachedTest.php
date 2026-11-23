<?php

declare(strict_types=1);

namespace Tests\App\Prospecting;

use App\Shared\ScheduledTasksFunction;
use Bref\Context\Context;
use Prospecter\Core\PersonalData\Domain\Model\ContactCard;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\Prospecting\Application\Port\ContactsToAttachRetriever;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\ContactCorrespondent;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\ContactToAttach;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\Noting;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Repository\ContactToAttachRepository;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\Contact\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Gesture\Gesture;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Base\ApiTestCase;

/**
 * The contacts nobody attached in the 30 days after their last meeting are erased with their exchanges (ADR 0146),
 * by `app:contacts:erase-unattached` and by the function the daily schedule of an instance calls, as many times as
 * asked, a run missed made up by the next; the clock of the tests says it is 2026-10-15 09:00 UTC. The contacts to
 * attach are listed by their holder and by the moment they are erased from, in every account.
 */
class EraseContactsLeftUnattachedTest extends ApiTestCase
{
    public function testTheConsoleErasesTheContactsLeftUnattachedOnly(): void
    {
        $left = $this->aContactToAttachMetOn('2026-09-14 10:00 Europe/Paris');
        $waiting = $this->aContactToAttachMetOn('2026-09-16 10:00 Europe/Paris');

        $this->assertStringContainsString('1 contact(s) left unattached erased', $this->erase());

        $this->assertSame([], self::service(PersonRepository::class)->ofIds([new PersonId($left)]));
        $this->assertSame([], self::service(ExchangeRepository::class)->withContact(new ContactId($left), $this->accountOfTheFixtures()));
        $this->assertNull(self::service(ContactToAttachRepository::class)->find(new ContactId($left)));
        $this->assertNotNull(self::service(ContactToAttachRepository::class)->find(new ContactId($waiting)));
        $this->assertStringContainsString('0 contact(s) left unattached erased', $this->erase());
    }

    public function testTheDailyFunctionOfTheScheduleErasesThemToo(): void
    {
        $left = $this->aContactToAttachMetOn('2026-09-14 10:00 Europe/Paris');

        $answer = self::service(ScheduledTasksFunction::class)->handle([], new Context('request', 0, 'function', 'trace'));

        $this->assertSame(['erased' => 1], $answer['ran']['erase-unattached']);

        $this->assertNull(self::service(ContactToAttachRepository::class)->find(new ContactId($left)));
    }

    public function testTheDailyFunctionOfTheScheduleMakesUpARunMissed(): void
    {
        $dueTheDayBeforeYesterday = $this->aContactToAttachMetOn('2026-09-13 10:00 Europe/Paris');
        $dueYesterday = $this->aContactToAttachMetOn('2026-09-14 10:00 Europe/Paris');

        $answer = self::service(ScheduledTasksFunction::class)->handle([], new Context('request', 0, 'function', 'trace'));

        $this->assertSame(['erased' => 2], $answer['ran']['erase-unattached']);
        $this->assertNull(self::service(ContactToAttachRepository::class)->find(new ContactId($dueTheDayBeforeYesterday)));
        $this->assertNull(self::service(ContactToAttachRepository::class)->find(new ContactId($dueYesterday)));
    }

    public function testTheContactsToAttachAreReadByHolderAndByTheMomentTheyAreErasedFrom(): void
    {
        $later = $this->aContactToAttachMetOn('2026-09-20 10:00 Europe/Paris');
        $sooner = $this->aContactToAttachMetOn('2026-09-14 23:30 Europe/Paris');
        $retriever = self::service(ContactsToAttachRetriever::class);

        $held = array_column($retriever->heldBy(new MemberId($this->memberIdOf('prospector-user'))), 'personId');
        $this->assertSame([$sooner, $later], array_values(array_intersect($held, [$sooner, $later])));
        $this->assertEquals(new \DateTimeImmutable('2026-10-13 22:00 UTC'), $retriever->findHeldBy(new MemberId($this->memberIdOf('prospector-user')), new ContactId($sooner))?->erasableFrom);
        $this->assertNull($retriever->findHeldBy(new MemberId($this->memberIdOf('assistant-user')), new ContactId($sooner)));
        $this->assertSame([$sooner], $retriever->leftUnattachedAt(new \DateTimeImmutable('2026-10-13 22:00 UTC')));
        $this->assertSame([], $retriever->leftUnattachedAt(new \DateTimeImmutable('2026-10-13 21:59 UTC')));
    }

    private function erase(): string
    {
        $tester = new CommandTester(new Application(self::$kernel ?? self::bootKernel())->find('app:contacts:erase-unattached'));
        $tester->execute([]);

        return $tester->getDisplay();
    }

    private function aContactToAttachMetOn(string $meeting): string
    {
        $account = new AccountId($this->accountOfTheFixtures()->value);
        $member = new MemberId($this->memberIdOf('prospector-user'));
        $at = new \DateTimeImmutable($meeting);
        $person = Person::collectToAttach(new Reader($member, $account, new PersonId(self::newIdentity()), true), new PersonId(self::newIdentity()), new ContactCard(new PersonName('Ada', 'Lovelace'), new ContactDetails(new Email(self::newIdentity().'@acme.test'))), $at);
        self::service(PersonRepository::class)->save($person);
        self::service(ContactToAttachRepository::class)->save(ContactToAttach::metBy(new Prospector($member, $account, managesPersonalData: true), new ContactId($person->id()->value), $at, $at));
        self::service(ExchangeRepository::class)->save(Exchange::noted(new Gesture($account, $member, $at), new ExchangeId(self::newIdentity()), new ContactCorrespondent(new ContactId($person->id()->value), []), Noting::of(new ExchangeFacts(Channel::VideoCall, Direction::Outbound, $at), ExchangeStatus::Done)));

        return $person->id()->value;
    }
}
