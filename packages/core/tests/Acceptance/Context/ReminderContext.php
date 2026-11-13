<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Accounts\Application\ChangeMyReminders\ChangeMyReminders;
use Prospecter\Core\Accounts\Application\MyReminders;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\Core\Acceptance\Expect;

/**
 * The reminders of a member: whether and how long before a planned exchange they are warned.
 */
final class ReminderContext implements Context
{
    use SharesTheScenario;

    private ?MyReminders $reminders = null;

    #[When('je consulte mes réglages de rappel')]
    public function iConsultMyReminders(): void
    {
        $this->reminders = $this->application->consultMyReminders();
    }

    #[When('je règle mes rappels à :minutes minutes avant')]
    public function iSetMyRemindersTo(int $minutes): void
    {
        $this->application->dispatch(new ChangeMyReminders($this->application->signedIn(), true, $minutes, $this->application->now()));
    }

    /**
     * As the screen does: the switch turned off, the delay as it was, for when it is turned on again.
     */
    #[When('je désactive mes rappels')]
    public function iTurnMyRemindersOff(): void
    {
        $this->iConsultMyReminders();
        Expect::that(null !== $this->reminders, 'My reminders were expected');
        $this->application->dispatch(new ChangeMyReminders($this->application->signedIn(), false, $this->reminders->minutesBefore, $this->application->now()));
    }

    #[Given('un autre membre de :account a réglé ses rappels à :minutes minutes avant')]
    public function anotherMemberSetTheirReminders(string $account, int $minutes): void
    {
        $colleague = $this->scenario->aMemberOf('un autre membre', $account, Role::User, Role::Prospector);
        $this->application->dispatch(new ChangeMyReminders($colleague->id()->value, true, $minutes, $this->application->now()));
    }

    #[Then('les rappels sont activés, :minutes minutes avant')]
    public function remindersAreOn(int $minutes): void
    {
        $this->expectMyReminders(enabled: true, minutesBefore: $minutes);
    }

    #[Then('les rappels sont désactivés, :minutes minutes avant')]
    public function remindersAreOffKeepingTheirDelay(int $minutes): void
    {
        $this->expectMyReminders(enabled: false, minutesBefore: $minutes);
    }

    #[Then('les rappels sont désactivés')]
    public function remindersAreOff(): void
    {
        $this->iConsultMyReminders();
        Expect::same(false, $this->reminders?->enabled);
    }

    private function expectMyReminders(bool $enabled, int $minutesBefore): void
    {
        $this->iConsultMyReminders();
        Expect::that(null !== $this->reminders, sprintf('My reminders were expected, got: %s', $this->application->refusal()?->getMessage()));
        Expect::same(['enabled' => $enabled, 'minutesBefore' => $minutesBefore], ['enabled' => $this->reminders->enabled, 'minutesBefore' => $this->reminders->minutesBefore]);
    }
}
