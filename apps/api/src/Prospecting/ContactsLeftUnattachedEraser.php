<?php

declare(strict_types=1);

namespace App\Prospecting;

use App\Shared\CommandBus;
use App\Shared\ScheduledTask;
use Prospecter\Core\Prospecting\Application\Contact\EraseContactLeftUnattached\EraseContactLeftUnattached;
use Prospecter\Core\Prospecting\Application\Port\ContactsToAttachRetriever;
use Symfony\Component\Clock\ClockInterface;

/**
 * Erases each contact left unattached at this moment, in every account (ADR 0146). An instance's daily schedule runs
 * it, and so does the console. Each contact gets its own command, so a contact is erased once, however often the
 * eraser runs.
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
     * @return int the number of contacts erased
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
