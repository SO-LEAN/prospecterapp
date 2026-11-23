<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Policy;

use Prospecter\Core\Campaigns\Application\RecordContactFacts\RecordContactFacts;
use Prospecter\Core\Campaigns\Application\RecordOrganizationFacts\RecordOrganizationFacts;
use Prospecter\Core\PersonalData\Contract\Event\ContactFactsChanged;
use Prospecter\Core\Prospecting\Contract\Event\OrganizationFactsChanged;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When the facts of an organization or a contact change, Campaigns records what the tags derive from: the contacts an
 * organization names, where it is, and how a contact is reached (ADR 0141, 0142).
 */
final readonly class RecordFactsPolicy implements Policy
{
    public function __invoke(OrganizationFactsChanged|ContactFactsChanged $event): RecordOrganizationFacts|RecordContactFacts|null
    {
        return $event instanceof OrganizationFactsChanged ? self::organizationFacts($event) : self::contactFacts($event);
    }

    private static function organizationFacts(OrganizationFactsChanged $event): ?RecordOrganizationFacts
    {
        if (null === $event->contacts) {
            return null;
        }

        return new RecordOrganizationFacts(
            accountId: $event->accountId,
            organizationId: $event->organizationId,
            revision: $event->revision,
            present: $event->present,
            contacts: $event->contacts,
            municipalityCode: $event->municipalityCode,
            country: $event->country,
            executedAt: $event->occurredAt,
        );
    }

    private static function contactFacts(ContactFactsChanged $event): ?RecordContactFacts
    {
        if (null === $event->hasEmail || null === $event->hasPhone || null === $event->hasMobile || null === $event->hasLinkedIn) {
            return null;
        }

        return new RecordContactFacts(
            accountId: $event->accountId,
            contactId: $event->personId,
            revision: $event->revision,
            present: $event->present,
            hasEmail: $event->hasEmail,
            hasPhone: $event->hasPhone,
            hasMobile: $event->hasMobile,
            hasLinkedIn: $event->hasLinkedIn,
            executedAt: $event->occurredAt,
        );
    }
}
