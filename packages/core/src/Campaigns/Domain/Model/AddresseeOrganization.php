<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * An organization a contact works for: its name, and when the contact was attached to it. An attachment recorded
 * before that moment was kept has none.
 */
final readonly class AddresseeOrganization
{
    public function __construct(
        public string $name,
        public ?\DateTimeImmutable $attachedAt,
    ) {
    }

    /**
     * The organization a message names: the one the contact was attached to most recently. An attachment without its
     * moment is older than any other. Among attachments of the same moment, or without one, the first is shown.
     *
     * @param list<self> $organizations
     */
    public static function shownAmong(array $organizations): ?self
    {
        return array_reduce($organizations, static fn (?self $shown, self $organization): self => null === $shown || $organization->isMoreRecentThan($shown) ? $organization : $shown);
    }

    /**
     * PHP orders any moment after none.
     */
    private function isMoreRecentThan(self $other): bool
    {
        return $this->attachedAt > $other->attachedAt;
    }
}
