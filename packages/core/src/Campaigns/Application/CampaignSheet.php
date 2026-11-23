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
     * @param list<string>                    $tags            the chosen tags, with their `#`
     * @param list<string>                    $targetedTags    the tags a targeted contact carries: the chosen tags and the channel's
     * @param string                          $messageMode     how an AI treats its message for each person: `asIs` or `adapted`
     * @param string                          $status          `draft`, `frozen`, `started`, `stopped` or `ended`
     * @param bool                            $followsTheTags  whether it targets what its tag search finds now, as its status
     *                                                         says: a draft
     * @param bool                            $running         whether it runs now, as its status says: started
     * @param int                             $processed       its recipients that an exchange processed, 0 before it starts
     * @param int                             $dismissed       its dismissed recipients, 0 before it starts
     * @param array<string, RecipientInSheet> $recipients      each recipient by contact id, in the order added; none in a draft
     * @param list<string>                    $possibleIntents the names of the intents its state allows (`ReviseCampaign`,
     *                                                         `DiscardCampaign`, `FreezeCampaign`, `AddRecipients`,
     *                                                         `RemoveRecipient`, `StartCampaign`, `StopCampaign`, `RunCampaign`)
     * @param int|null                        $dailyCap        the most recipients processed in a day, only a guard; null when not set
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
