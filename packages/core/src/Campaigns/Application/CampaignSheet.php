<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application;

/**
 * A campaign as the team reads it (ADR 0144).
 */
final readonly class CampaignSheet
{
    /**
     * @param string                          $channel         `email`, `call`, `sms` or `linkedIn`
     * @param list<string>                    $tags            the tags chosen, with their mark
     * @param list<string>                    $targetedTags    the tags a contact carries to be targeted: those chosen and that of the channel
     * @param string                          $messageMode     how an AI treats its message for each person: `asIs` or `adapted`
     * @param string                          $status          `draft`, `frozen`, `started`, `stopped` or `ended`
     * @param bool                            $followsTheTags  whether whom it targets is the search by its tags as the contacts
     *                                                         carry them now, as the domain says of its status: a draft
     * @param bool                            $running         whether it is led now, as the domain says of its status: started
     * @param int                             $processed       its recipients processed by an exchange, none before it is started
     * @param int                             $dismissed       its recipients dismissed, none before it is started
     * @param array<string, RecipientInSheet> $recipients      each recipient, by contact identity, in the order they were
     *                                                         added; none in a draft
     * @param list<string>                    $possibleIntents the names of the intents its state offers (`ReviseCampaign`,
     *                                                         `DiscardCampaign`, `FreezeCampaign`, `AddRecipients`,
     *                                                         `RemoveRecipient`, `StartCampaign`, `StopCampaign`, `RunCampaign`)
     * @param int|null                        $dailyCap        the most recipients processed a day, a guard only; none when not set
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
        public string $messageMode,
        public string $status,
        public bool $followsTheTags,
        public bool $running,
        public int $processed,
        public int $dismissed,
        public array $recipients,
        public string $authorId,
        public array $possibleIntents,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $modifiedAt,
        public ?\DateTimeImmutable $startedAt,
        public ?\DateTimeImmutable $stoppedAt,
        public ?\DateTimeImmutable $endedAt,
        public ?int $dailyCap = null,
    ) {
    }
}
