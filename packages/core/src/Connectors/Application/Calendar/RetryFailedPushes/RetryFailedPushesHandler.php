<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Calendar\RetryFailedPushes;

use Prospecter\Core\Connectors\Application\Calendar\CalendarPusher;
use Prospecter\Core\Connectors\Domain\Model\CalendarEntry;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\Connectors\Domain\Repository\CalendarEntryRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * The member pushes again each of their exchanges that the provider refused (ADR 0133).
 */
final readonly class RetryFailedPushesHandler implements CommandHandler
{
    public function __construct(
        private CalendarEntryRepository $entries,
        private Members $members,
        private CalendarPusher $pusher,
    ) {
    }

    public function __invoke(RetryFailedPushes $command): void
    {
        $me = $this->members->get(new MemberId($command->memberId));
        $failed = array_filter($this->entries->ofMember($me->id), static fn (CalendarEntry $entry): bool => $entry->hasFailed());

        foreach ($failed as $entry) {
            $this->pusher->follow($entry->exchange(), $me->accountId, $command->executedAt);
        }
    }
}
