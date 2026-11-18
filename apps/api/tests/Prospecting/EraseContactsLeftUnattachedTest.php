<?php

namespace Tests\App\Prospecting;

use App\Prospecting\EraseContactsLeftUnattachedFunction;
use Bref\Context\Context;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\Prospecting\Application\Port\ContactsToAttachRetriever;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\ContactToAttach;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Repository\ContactToAttachRepository;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Base\ApiTestCase;

/**
 * The contacts nobody attached in the 30 days after their last meeting are erased with their exchanges (ADR 0146),
 * by `app:contacts:erase-unattached` and by the function the schedule of an instance calls (ADR 0147), as many times
 * as asked; the clock of the tests says it is 2026-10-15 09:00 UTC. The contacts to attach are listed by their
 * holder and by the moment they are erased from, in every account.
 */
class EraseContactsLeftUnattachedTest extends ApiTestCase
{
    public function testTheConsoleErasesTheContactsLeftUnattachedOnly(): void
    {
        $left = $this->aContactToAttachMetOn('2026-09-14 10:00 Europe/Paris');
        $waiting = $this->aContactToAttachMetOn('2026-09-16 10:00 Europe/Paris');

        $this->assertStringContainsString('1 contact(s) left unattached erased', $this->erase());

        $this->assertSame([], self::getContainer()->get(PersonRepository::class)->ofIds([new PersonId($left)]));
        $this->assertSame([], self::getContainer()->get(ExchangeRepository::class)->withContact(new PersonId($left), $this->accountOfTheFixtures()));
        $this->assertNull(self::getContainer()->get(ContactToAttachRepository::class)->find(new PersonId($left)));
        $this->assertNotNull(self::getContainer()->get(ContactToAttachRepository::class)->find(new PersonId($waiting)));
        $this->assertStringContainsString('0 contact(s) left unattached erased', $this->erase());
    }

    public function testTheFunctionOfTheScheduleErasesThemToo(): void
    {
        $left = $this->aContactToAttachMetOn('2026-09-14 10:00 Europe/Paris');

        self::getContainer()->get(EraseContactsLeftUnattachedFunction::class)->handle([], new Context('request', 0, 'function', 'trace'));

        $this->assertNull(self::getContainer()->get(ContactToAttachRepository::class)->find(new PersonId($left)));
    }

    public function testTheContactsToAttachAreReadByHolderAndByTheMomentTheyAreErasedFrom(): void
    {
        $later = $this->aContactToAttachMetOn('2026-09-20 10:00 Europe/Paris');
        $sooner = $this->aContactToAttachMetOn('2026-09-14 23:30 Europe/Paris');
        $retriever = self::getContainer()->get(ContactsToAttachRetriever::class);

        $held = array_column($retriever->heldBy(new MemberId($this->memberIdOf('prospector-user'))), 'personId');
        $this->assertSame([$sooner, $later], array_values(array_intersect($held, [$sooner, $later])));
        $this->assertEquals(new \DateTimeImmutable('2026-10-13 22:00 UTC'), $retriever->findHeldBy(new MemberId($this->memberIdOf('prospector-user')), new PersonId($sooner))?->erasableFrom);
        $this->assertNull($retriever->findHeldBy(new MemberId($this->memberIdOf('assistant-user')), new PersonId($sooner)));
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
        $person = Person::collectToAttach(new Reader($member, $account, new PersonId(self::newIdentity()), true), new PersonId(self::newIdentity()), new PersonName('Ada', 'Lovelace'), new ContactDetails(new Email(self::newIdentity().'@acme.test')), $at);
        self::getContainer()->get(PersonRepository::class)->save($person);
        self::getContainer()->get(ContactToAttachRepository::class)->save(ContactToAttach::metBy(new Prospector($member, $account, managesPersonalData: true), $person->id(), $at, $at));
        self::getContainer()->get(ExchangeRepository::class)->save(Exchange::notedWithAContact($account, $member, new ExchangeId(self::newIdentity()), $person->id(), [], new ExchangeFacts(Channel::VideoCall, Direction::Outbound, $at), ExchangeStatus::Done, $at));

        return $person->id()->value;
    }
}
