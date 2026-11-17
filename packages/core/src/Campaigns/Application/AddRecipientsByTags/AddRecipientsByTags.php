<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\AddRecipientsByTags;

final readonly class AddRecipientsByTags
{
    /**
     * @param list<string> $tags the tags of the search, as the member typed them, that of the channel added
     */
    public function __construct(
        public string $memberId,
        public string $campaignId,
        public array $tags,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
