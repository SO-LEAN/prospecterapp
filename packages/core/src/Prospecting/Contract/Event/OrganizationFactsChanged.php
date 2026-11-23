<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Event;

/**
 * An integration event published to the other contexts (ADR 0046, 0047). It gives an organization's state after a
 * change, as its facts count it. The highest revision is the latest. An organization the account no longer prospects
 * is absent, at revision 0, and names no contact.
 * The event publishes the contacts since its second version: they are null in a fact of the first version. It
 * publishes the municipality's INSEE code and the address's country since its third version (ADR 0142): they are null
 * in a fact of an earlier version.
 */
final readonly class OrganizationFactsChanged
{
    /**
     * @param list<string>|null $contacts the persons the organization names as its contacts
     */
    public function __construct(
        public string $accountId,
        public string $organizationId,
        public int $revision,
        public bool $present,
        public bool $located,
        public ?array $contacts,
        public ?string $municipalityCode,
        public ?string $country,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}
