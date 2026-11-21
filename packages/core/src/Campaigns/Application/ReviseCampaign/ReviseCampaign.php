<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ReviseCampaign;

/**
 * Everything of a draft changed at once, as the member left it.
 */
final readonly class ReviseCampaign
{
    /**
     * @param string       $channel     `email`, `call`, `sms` or `linkedIn`
     * @param list<string> $tags        the tags chosen, as the member typed them
     * @param string|null  $subject     the subject of an e-mail
     * @param string       $messageMode how an AI treats the message for each person: `asIs` or `adapted`
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
        public string $messageMode = 'asIs',
    ) {
    }
}
