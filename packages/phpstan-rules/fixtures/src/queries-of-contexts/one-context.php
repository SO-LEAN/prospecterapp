<?php

declare(strict_types=1);

namespace App\Fixtures;

use Prospecter\Core\Prospecting\Application\Timeline\ConsultTimeline\ConsultTimeline;
use Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts\NameOrganizationsOfContacts;

final readonly class AsksOneContext
{
    public function organizations(): NameOrganizationsOfContacts
    {
        return new NameOrganizationsOfContacts('account', ['person']);
    }

    public function timeline(): ConsultTimeline
    {
        return new ConsultTimeline(prospectorId: 'prospector', personId: 'person', asOf: new \DateTimeImmutable('2026-11-27'));
    }
}
