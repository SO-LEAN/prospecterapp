<?php

declare(strict_types=1);

namespace App\Fixtures;

use Prospecter\Core\PersonalData\Contract\Service\NameContacts\NameContacts;
use Prospecter\Core\Prospecting\Application\Timeline\ConsultTimeline\ConsultTimeline;

final readonly class AsksTwoContexts
{
    public function names(): NameContacts
    {
        return new NameContacts('reader', ['person']);
    }

    public function timeline(): ConsultTimeline
    {
        return new ConsultTimeline(prospectorId: 'prospector', personId: 'person', asOf: new \DateTimeImmutable('2026-11-27'));
    }
}
