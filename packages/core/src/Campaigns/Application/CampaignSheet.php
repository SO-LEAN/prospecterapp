<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application;

/**
 * A campaign as the team reads it (ADR 0144).
 */
final readonly class CampaignSheet
{
    /**
     * @param string                $channel         `email`, `call`, `sms` or `linkedIn`
     * @param list<string>          $tags            the tags chosen, with their mark
     * @param list<string>          $targetedTags    the tags a contact carries to be targeted: those chosen and that of the channel
     * @param string                $status          `draft` or `frozen`
     * @param bool                  $followsTheTags  whether whom it targets is the search by its tags as the contacts
     *                                               carry them now, as the domain says of its status: a draft
     * @param int                   $contacted       the contacts of its target the team contacted, none before it is
     *                                               started
     * @param array<string, string> $recipients      the status of each recipient (`pending`), by contact identity, in
     *                                               the order they were added; none in a draft
     * @param list<string>          $possibleIntents the names of the intents its state offers (`ReviseCampaign`,
     *                                               `DiscardCampaign`, `FreezeCampaign`, `AddRecipients`,
     *                                               `RemoveRecipient`)
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
        public bool $followsTheTags,
        public int $contacted,
        public array $recipients,
        public string $authorId,
        public array $possibleIntents,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $modifiedAt,
    ) {
    }
}
