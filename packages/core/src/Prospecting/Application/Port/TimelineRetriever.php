<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Timeline;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Reads the timelines in slices, the most recent moment first, after the cursor (ADR 0049, 0074, 0076, 0079),
 * without restoring the exchanges (ADR 0088): each as its reader may read it (`ExchangeReading`), with what is to
 * make conform (`ExchangeConformity`) and where its contact stands (`ContactPresence`).
 */
interface TimelineRetriever
{
    /**
     * The exchanges with the contact in the account of the reader.
     */
    public function ofContact(PersonId $contact, Prospector $reader, ?string $after, int $max): Timeline;

    /**
     * The exchanges kept for the organization, with it or with its contacts, counting those to make conform.
     */
    public function ofOrganization(OrganizationSheet $organization, Prospector $reader, ?string $after, int $max): Timeline;

    /**
     * Only those to make conform of the timeline of the organization.
     */
    public function toMakeConformIn(OrganizationSheet $organization, Prospector $reader, ?string $after, int $max): Timeline;
}
