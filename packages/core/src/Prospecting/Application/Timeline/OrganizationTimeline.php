<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Timeline;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * An organization's timeline as Prospecting shows it (ADR 0217): a slice of its exchanges, the organization, and what
 * Connectors lets the reader do with the e-mails exchanged with the organization itself. The persons its exchanges were
 * with are PersonalData's, named next to it by their ids.
 */
final readonly class OrganizationTimeline
{
    /**
     * @param list<string> $mailCatchUpIntents `CatchUpGmail`, on the timeline of the past only, or nothing
     */
    public function __construct(
        public Timeline $timeline,
        public OrganizationSheet $organization,
        public array $mailCatchUpIntents = [],
    ) {
    }
}
