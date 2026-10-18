<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultTimeline;

use Prospecter\Core\Prospecting\Domain\Exchange;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * An exchange in a timeline; its channel and its direction by the values of `Channel` and `Direction`.
 */
final readonly class ExchangeResponse
{
    /**
     * @param list<string> $organizationIds the organizations it is kept for
     */
    private function __construct(
        public string $id,
        public string $channel,
        public string $direction,
        public bool $missed,
        public \DateTimeImmutable $occurredAt,
        public ?string $text,
        public array $organizationIds,
    ) {
    }

    public static function of(Exchange $exchange): self
    {
        $facts = $exchange->facts();

        return new self(
            id: $exchange->id()->value,
            channel: $facts->channel->value,
            direction: $facts->direction->value,
            missed: $facts->missed,
            occurredAt: $facts->occurredAt,
            text: $facts->text,
            organizationIds: array_map(static fn (OrganizationId $organization): string => $organization->value, $exchange->keptFor()),
        );
    }
}
