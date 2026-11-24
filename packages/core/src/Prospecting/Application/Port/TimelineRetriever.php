<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Timeline\Timeline;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;

/**
 * Reads the timelines in slices after the cursor (ADR 0049, 0074, 0076, 0079). Past exchanges come most recent first.
 * Upcoming exchanges come soonest first. It does not restore the exchanges (ADR 0088). Each exchange reads as its
 * reader may read it (`ExchangeReading`). It says what still needs redacting (`ExchangeConformity`) and where its
 * contact stands (`ContactPresence`).
 */
interface TimelineRetriever
{
    /**
     * Returns the exchanges with the contact in the reader's account, up to the moment.
     */
    public function ofContact(ContactId $contact, Prospector $reader, \DateTimeImmutable $asOf, ?string $after, int $max): Timeline;

    /**
     * Returns the exchanges kept for the organization, up to the moment. They are with the organization or with its
     * contacts. The timeline counts those still to be redacted.
     */
    public function ofOrganization(OrganizationSheet $organization, Prospector $reader, \DateTimeImmutable $asOf, ?string $after, int $max): Timeline;

    /**
     * Returns the exchanges with the contact in the reader's account, after the moment.
     */
    public function upcomingWithContact(ContactId $contact, Prospector $reader, \DateTimeImmutable $asOf, ?string $after, int $max): Timeline;

    /**
     * Returns the exchanges kept for the organization, after the moment. They are with the organization or with its
     * contacts. The timeline counts those still to be redacted.
     */
    public function upcomingInOrganization(OrganizationSheet $organization, Prospector $reader, \DateTimeImmutable $asOf, ?string $after, int $max): Timeline;

    /**
     * Returns only the exchanges still to be redacted in the organization's timeline.
     */
    public function toMakeConformIn(OrganizationSheet $organization, Prospector $reader, ?string $after, int $max): Timeline;
}
