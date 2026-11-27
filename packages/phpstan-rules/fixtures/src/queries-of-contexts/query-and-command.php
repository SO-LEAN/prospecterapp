<?php

declare(strict_types=1);

namespace App\Fixtures;

use Prospecter\Core\Campaigns\Application\Tagging\PutTags\PutTags;
use Prospecter\Core\Prospecting\Application\Timeline\ConsultTimeline\ConsultTimeline;

final readonly class AsksOneContextAndCommandsAnother
{
    public function timeline(): ConsultTimeline
    {
        return new ConsultTimeline(prospectorId: 'prospector', personId: 'person', asOf: new \DateTimeImmutable('2026-11-27'));
    }

    public function tags(): PutTags
    {
        return new PutTags('member', 'organization', 'organization', ['tag'], new \DateTimeImmutable('2026-11-27'));
    }
}
