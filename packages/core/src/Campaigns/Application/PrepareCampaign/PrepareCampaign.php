<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\PrepareCampaign;

final readonly class PrepareCampaign
{
    /**
     * @param string       $channel     `email`, `call`, `sms` or `linkedIn`
     * @param list<string> $tags        the chosen tags, as the member typed them
     * @param string|null  $subject     an e-mail's subject
     * @param string|null  $messageMode how an AI treats the message for each person: `asIs` or `adapted`, `asIs` when not given
     * @param int|null     $dailyCap    the most recipients processed in a day, only a guard; null when not given
     */
    public function __construct(
        public string $memberId,
        public string $campaignId,
        public string $name,
        public string $channel,
        public array $tags,
        public string $message,
        public ?string $subject,
        public \DateTimeImmutable $executedAt,
        public ?string $messageMode = null,
        public ?int $dailyCap = null,
    ) {
    }
}
