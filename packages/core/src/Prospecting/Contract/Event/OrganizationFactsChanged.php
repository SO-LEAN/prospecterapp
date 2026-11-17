<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Event;

/**
 * Integration event published to the other contexts (ADR 0046, 0047): the state of an organization once it
 * changed, as its facts count it. The highest revision is the latest; an organization the account no longer
 * prospects is absent, at the revision 0, naming no contact. Its contacts are published from its second version: null
 * in a fact of the first; the INSEE code of its municipality and the country of its address from the third (ADR 0142):
 * null in a fact of a former one.
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
