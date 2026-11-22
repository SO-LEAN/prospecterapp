<?php

declare(strict_types=1);

namespace App\Campaigning\Input;

use OpenApi\Attributes as OA;
use Prospecter\Core\Campaigns\Application\PrepareCampaign\PrepareCampaign;
use Prospecter\Core\Campaigns\Application\ReviseCampaign\ReviseCampaign;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A campaign as the member leaves it, prepared or revised whole: the core decides what makes one (ADR 0144).
 */
final readonly class CampaignInput
{
    /**
     * @param list<string>|null $tags
     */
    public function __construct(
        #[Assert\NotNull]
        public ?string $name = null,
        #[Assert\NotNull]
        #[OA\Property(description: 'How each contact is contacted', enum: ['email', 'call', 'sms', 'linkedIn'])]
        public ?string $channel = null,
        #[Assert\NotNull]
        #[Assert\All([new Assert\Type('string')])]
        #[OA\Property(description: 'The tags chosen, the tag of the channel counting with them in the 5 tags of the target', type: 'array', items: new OA\Items(type: 'string'))]
        public ?array $tags = null,
        #[Assert\NotNull]
        #[OA\Property(description: 'The message reminded for each contact, its variables {title}, {firstName}, {lastName}, {organization} and {jobTitle}')]
        public ?string $message = null,
        #[OA\Property(description: 'The subject of an e-mail')]
        public ?string $subject = null,
        #[OA\Property(description: 'How an AI leading it treats the message for each person: sent as rendered, or adapted to them, its idea kept; as is when left out', enum: ['asIs', 'adapted'])]
        public ?string $messageMode = null,
        #[OA\Property(description: 'The most recipients processed a day, a guard only; none when left out. Read as it is prepared: it changes then by its own endpoint')]
        public ?int $dailyCap = null,
    ) {
    }

    public function preparationOf(string $memberId, string $campaignId, \DateTimeImmutable $executedAt): PrepareCampaign
    {
        return new PrepareCampaign(
            memberId: $memberId,
            campaignId: $campaignId,
            name: (string) $this->name,
            channel: (string) $this->channel,
            tags: $this->tags ?? [],
            message: (string) $this->message,
            subject: $this->subject,
            executedAt: $executedAt,
            messageMode: $this->messageMode,
            dailyCap: $this->dailyCap,
        );
    }

    public function revisionOf(string $memberId, string $campaignId, \DateTimeImmutable $executedAt): ReviseCampaign
    {
        return new ReviseCampaign(
            memberId: $memberId,
            campaignId: $campaignId,
            name: (string) $this->name,
            channel: (string) $this->channel,
            tags: $this->tags ?? [],
            message: (string) $this->message,
            subject: $this->subject,
            executedAt: $executedAt,
            messageMode: $this->messageMode,
        );
    }
}
