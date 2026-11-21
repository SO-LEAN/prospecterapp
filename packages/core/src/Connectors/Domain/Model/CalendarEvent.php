<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A planned exchange as its holder finds it in their calendar at a provider (ADR 0127): when, how and where, with
 * whom, the card it leads to (that of the contact, or of the organization itself), and the reminder before it, none
 * when the holder turned their reminders off; a video call without a link may ask the provider to create one (ADR
 * 0137). The adapter of the provider words it.
 */
final readonly class CalendarEvent
{
    public function __construct(
        public ExchangeId $exchange,
        public Channel $channel,
        public \DateTimeImmutable $startsAt,
        public \DateTimeImmutable $endsAt,
        public ?string $venue,
        public ?PersonId $contact,
        public ?string $contactName,
        public ?OrganizationId $organization,
        public ?string $organizationName,
        public ?int $reminderMinutesBefore,
        public bool $createsALink = false,
        public ?Guest $guest = null,
    ) {
    }
}
