<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Application\MyReminders;
use Prospecter\Core\Accounts\Application\Port\MyRemindersRetriever;
use Prospecter\Core\Accounts\Domain\Exception\UnknownMember;
use Prospecter\Core\Accounts\Domain\Model\Reminders;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Reads the reminders of the members that the in-memory repository saved, as the adapter reads the member's item.
 */
final readonly class InMemoryMyRemindersRetriever implements MyRemindersRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function of(MemberId $me): MyReminders
    {
        $member = $this->database->member((string) $me) ?? throw UnknownMember::identifiedBy($me);
        $reminders = StateOf::the($member)->object('reminders', Reminders::class);

        return new MyReminders($reminders->enabled, $reminders->delay->value);
    }
}
