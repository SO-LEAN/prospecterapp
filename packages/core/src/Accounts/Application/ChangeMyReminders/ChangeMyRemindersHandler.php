<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ChangeMyReminders;

use Prospecter\Core\Accounts\Domain\Model\ReminderDelay;
use Prospecter\Core\Accounts\Domain\Model\Reminders;
use Prospecter\Core\Accounts\Domain\Repository\MemberRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ChangeMyRemindersHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private MemberRepository $members,
        private Events $events,
    ) {
    }

    public function __invoke(ChangeMyReminders $command): void
    {
        $me = $this->members->get(new MemberId($command->memberId));
        $me->changeReminders(new Reminders($command->enabled, ReminderDelay::ofMinutes($command->minutesBefore)), at: $command->executedAt);
        $this->members->save($me);
        $this->publishWhatHappenedTo($me);
    }
}
