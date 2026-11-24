<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Recipient\AddRecipientsByTags;

final readonly class AddRecipientsByTags
{
    /**
     * @param list<string> $tags the search's tags as the member typed them; the channel's tag is added to them
     */
    public function __construct(
        public string $memberId,
        public string $campaignId,
        public array $tags,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
