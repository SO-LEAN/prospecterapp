<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\PrepareCampaign;

final readonly class PrepareCampaign
{
    /**
     * @param string       $channel     `email`, `call`, `sms` or `linkedIn`
     * @param list<string> $tags        the tags chosen, as the member typed them
     * @param string|null  $subject     the subject of an e-mail
     * @param string|null  $messageMode how an AI treats the message for each person: `asIs` or `adapted`, as is when not said
     * @param int|null     $dailyCap    the most recipients processed a day, a guard only; none when not said
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
