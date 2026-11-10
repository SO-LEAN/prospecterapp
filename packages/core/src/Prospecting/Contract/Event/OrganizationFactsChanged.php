<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Event;

/**
 * Integration event published to the other contexts (ADR 0046, 0047): the state of an organization once it
 * changed, as its facts count it. The highest revision is the latest; an organization the account no longer
 * prospects is absent, at the revision 0.
 */
final readonly class OrganizationFactsChanged
{
    public function __construct(
        public string $accountId,
        public string $organizationId,
        public int $revision,
        public bool $present,
        public bool $located,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}
