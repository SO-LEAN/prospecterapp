<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Timeline;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * An organization's timeline as Prospecting shows it (ADR 0217): a slice of its exchanges and the organization. The
 * persons its exchanges were with are PersonalData's, named next to it by their ids.
 */
final readonly class OrganizationTimeline
{
    public function __construct(
        public Timeline $timeline,
        public OrganizationSheet $organization,
    ) {
    }
}
