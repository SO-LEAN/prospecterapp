<?php

namespace App\Prospecting;

use App\Shared\CommandBus;
use App\Shared\ScheduledTask;
use Prospecter\Core\Prospecting\Application\EraseContactLeftUnattached\EraseContactLeftUnattached;
use Prospecter\Core\Prospecting\Application\Port\ContactsToAttachRetriever;
use Symfony\Component\Clock\ClockInterface;

/**
 * Gives each contact left unattached at this moment, in every account, the erasure of its own (ADR 0146): what the
 * schedule of an instance runs, and the console (ADR 0147). Each is a command of its own, erasing it once whoever
 * asks again.
 */
final readonly class ContactsLeftUnattachedEraser implements ScheduledTask
{
    public function __construct(
        private ContactsToAttachRetriever $contactsToAttach,
        private CommandBus $commands,
        private ClockInterface $clock,
    ) {
    }

    public function name(): string
    {
        return 'erase-unattached';
    }

    public function run(): array
    {
        return ['erased' => $this->erase()];
    }

    /**
     * @return int how many it erased
     */
    public function erase(): int
    {
        $now = $this->clock->now();
        $leftUnattached = $this->contactsToAttach->leftUnattachedAt($now);

        foreach ($leftUnattached as $person) {
            $this->commands->dispatch(new EraseContactLeftUnattached($person, $now));
        }

        return count($leftUnattached);
    }
}
