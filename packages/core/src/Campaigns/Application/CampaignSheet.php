<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application;

/**
 * A campaign as the team reads it (ADR 0144).
 */
final readonly class CampaignSheet
{
    /**
     * @param string       $channel         `email`, `call`, `sms` or `linkedIn`
     * @param list<string> $tags            the tags chosen, with their mark
     * @param list<string> $targetedTags    the tags a contact carries to be targeted: those chosen and that of the channel
     * @param string       $status          `draft`
     * @param list<string> $possibleIntents the names of the intents its state offers (`ReviseCampaign`, `DiscardCampaign`)
     */
    public function __construct(
        public string $id,
        public string $accountId,
        public string $name,
        public string $channel,
        public array $tags,
        public array $targetedTags,
        public string $message,
        public ?string $subject,
        public string $status,
        public string $authorId,
        public array $possibleIntents,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $modifiedAt,
    ) {
    }
}
