<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Timeline;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\PersonsShown;

/**
 * An organization's timeline as its screen shows it (ADR 0217): a slice of its exchanges, the organization, the persons
 * its exchanges were with, as PersonalData lets the reader read them, and what Connectors lets the reader do with the
 * e-mails exchanged with the organization itself.
 */
final readonly class OrganizationTimeline
{
    /**
     * @param list<string> $mailCatchUpIntents `CatchUpGmail`, on the timeline of the past only, or nothing
     */
    public function __construct(
        public Timeline $timeline,
        public OrganizationSheet $organization,
        public PersonsShown $persons,
        public array $mailCatchUpIntents = [],
    ) {
    }
}
