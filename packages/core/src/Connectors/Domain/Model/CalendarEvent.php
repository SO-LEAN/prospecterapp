<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

/**
 * A planned exchange as its holder finds it in their calendar at a provider (ADR 0127). It holds when, how and where
 * the exchange happens, and with whom. It holds the card it leads to: the contact's card, or the organization's own
 * card. It holds the reminder before it, or none when the holder turned their reminders off. A video call without a
 * link may ask the provider to create one (ADR 0137). The provider's adapter words the event.
 */
final readonly class CalendarEvent
{
    public function __construct(
        public ExchangeId $exchange,
        public Channel $channel,
        public \DateTimeImmutable $startsAt,
        public \DateTimeImmutable $endsAt,
        public ?string $venue,
        public ?ContactId $contact,
        public ?string $contactName,
        public ?OrganizationId $organization,
        public ?string $organizationName,
        public ?int $reminderMinutesBefore,
        public bool $createsALink = false,
        public ?Guest $guest = null,
    ) {
    }
}
