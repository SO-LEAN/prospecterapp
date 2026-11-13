<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Event\ToDoErased;
use Prospecter\Core\Prospecting\Domain\Event\ToDoLeftWithoutOrganization;
use Prospecter\Core\Prospecting\Domain\Event\ToDoNoLongerKeptFor;
use Prospecter\Core\Prospecting\Domain\Event\ToDoNoted;
use Prospecter\Core\Prospecting\Domain\Event\ToDoRevised;
use Prospecter\Core\Prospecting\Domain\Event\ToDoTickedOff;
use Prospecter\Core\Prospecting\Domain\Event\ToDoUnticked;
use Prospecter\Core\Prospecting\Domain\Exception\ToDoWithoutText;
use Prospecter\Core\Prospecting\Domain\Model\Due;
use Prospecter\Core\Prospecting\Domain\Model\ToDo;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\Prospecting\Domain\Model\ToDoIntent;
use Prospecter\Core\Prospecting\Domain\Policy\ToDoPossibilities;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\Core\Double\StateOf;

/**
 * Every intention of a to-do is refused exactly when its state does not offer it, and records its event.
 */
final class ToDoTest extends TestCase
{
    private const string ACCOUNT = '0190a1c0-0000-7000-8000-000000000001';
    private const string ME = '0190a1c0-0000-7000-8000-000000000002';
    private const string TO_DO = '0190a1c0-0000-7000-8000-000000000003';
    private const string CONTACT = '0190a1c0-0000-7000-8000-000000000004';
    private const string ACME = '0190a1c0-0000-7000-8000-000000000005';
    private const string GROUP = '0190a1c0-0000-7000-8000-000000000006';
    private const string NOW = '2026-11-13 10:00';

    public function testNotingRecordsIt(): void
    {
        $this->assertEquals([new ToDoNoted(new AccountId(self::ACCOUNT), new MemberId(self::ME), new ToDoId(self::TO_DO), $this->now())], $this->noted()->releaseEvents());
    }

    public function testItsTextIsWhatIsSaid(): void
    {
        $this->assertSame('Envoyer mon CV', StateOf::the($this->noted(text: '  Envoyer mon CV '))['text']);
    }

    public function testItIsNeverNotedWithoutAText(): void
    {
        $this->expectExceptionObject(ToDoWithoutText::because('A to-do has a text', 'text'));

        $this->noted(text: ' ');
    }

    #[DataProvider('intents')]
    public function testAnIntentIsRefusedExactlyWhenItsStateDoesNotOfferIt(ToDoIntent $intent, bool $done): void
    {
        $toDo = $done ? $this->done() : $this->noted();
        $toDo->releaseEvents();

        if (!in_array($intent, ToDoPossibilities::given($done), true)) {
            $this->expectException(NotPossibleInItsState::class);
        }

        $this->carryOut($intent, $toDo);

        $this->assertCount(1, $toDo->releaseEvents());
    }

    /**
     * @return iterable<string, array{ToDoIntent, bool}>
     */
    public static function intents(): iterable
    {
        foreach (ToDoIntent::cases() as $intent) {
            yield $intent->name.' to do' => [$intent, false];
            yield $intent->name.' done' => [$intent, true];
        }
    }

    public function testATickedOffToDoIsDoneAtThatMoment(): void
    {
        $this->assertEquals([new ToDoTickedOff(new AccountId(self::ACCOUNT), new MemberId(self::ME), new ToDoId(self::TO_DO), $this->now())], $this->done()->releaseEvents());
        $this->assertEquals($this->now(), StateOf::the($this->done())['doneAt']);
    }

    public function testAnUntickedToDoIsToDoAgain(): void
    {
        $toDo = $this->done();
        $toDo->untick(new MemberId(self::ME), $this->now());

        $this->assertNull(StateOf::the($toDo)['doneAt']);
        $this->assertContainsOnlyInstancesOf(ToDoUnticked::class, array_slice($toDo->releaseEvents(), -1));
    }

    public function testRevisingChangesItsTextAndWhenItIsDue(): void
    {
        $toDo = $this->noted();
        $toDo->revise('Relancer pour le devis', new Due('2026-11-20', '09:00'), new MemberId(self::ME), $this->now());

        $this->assertEquals(['Relancer pour le devis', new Due('2026-11-20', '09:00')], [StateOf::the($toDo)['text'], StateOf::the($toDo)['due']]);
        $this->assertContainsOnlyInstancesOf(ToDoRevised::class, array_slice($toDo->releaseEvents(), -1));
    }

    public function testARevisedToDoKeepsAText(): void
    {
        $this->expectException(ToDoWithoutText::class);

        $this->noted()->revise('', new Due('2026-11-20'), new MemberId(self::ME), $this->now());
    }

    public function testStillKeptForAnotherOrganizationItStays(): void
    {
        $toDo = $this->noted();
        $toDo->releaseEvents();
        $toDo->stopKeepingFor(new OrganizationId(self::ACME), new MemberId(self::ME), $this->now());

        $this->assertTrue($toDo->isKeptForAnOrganization());
        $this->assertEquals([new ToDoNoLongerKeptFor(new AccountId(self::ACCOUNT), new MemberId(self::ME), new ToDoId(self::TO_DO), new OrganizationId(self::ACME), $this->now())], $toDo->releaseEvents());
    }

    public function testKeptForNoOrganizationItMustNotStay(): void
    {
        $toDo = $this->noted();
        $toDo->stopKeepingFor(new OrganizationId(self::ACME), new MemberId(self::ME), $this->now());
        $toDo->releaseEvents();
        $toDo->stopKeepingFor(new OrganizationId(self::GROUP), new MemberId(self::ME), $this->now());

        $this->assertFalse($toDo->isKeptForAnOrganization());
        $this->assertInstanceOf(ToDoLeftWithoutOrganization::class, array_last($toDo->releaseEvents()));
    }

    public function testErasingRecordsIt(): void
    {
        $toDo = $this->noted();
        $toDo->releaseEvents();
        $toDo->erase(new MemberId(self::ME), $this->now());

        $this->assertEquals([new ToDoErased(new AccountId(self::ACCOUNT), new MemberId(self::ME), new ToDoId(self::TO_DO), $this->now())], $toDo->releaseEvents());
    }

    private function carryOut(ToDoIntent $intent, ToDo $toDo): void
    {
        $me = new MemberId(self::ME);

        match ($intent) {
            ToDoIntent::ReviseToDo => $toDo->revise('Relancer pour le devis', new Due('2026-11-20'), $me, $this->now()),
            ToDoIntent::TickOffToDo => $toDo->tickOff($me, $this->now()),
            ToDoIntent::UntickToDo => $toDo->untick($me, $this->now()),
            ToDoIntent::EraseToDo => $toDo->erase($me, $this->now()),
        };
    }

    private function noted(string $text = 'Envoyer mon CV'): ToDo
    {
        return ToDo::forAContact(new AccountId(self::ACCOUNT), new MemberId(self::ME), new ToDoId(self::TO_DO), new PersonId(self::CONTACT), [new OrganizationId(self::ACME), new OrganizationId(self::GROUP)], $text, new Due('2026-11-14'), $this->now());
    }

    private function done(): ToDo
    {
        $toDo = $this->noted();
        $toDo->releaseEvents();
        $toDo->tickOff(new MemberId(self::ME), $this->now());

        return $toDo;
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(self::NOW);
    }
}
