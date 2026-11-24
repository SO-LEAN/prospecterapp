<?php

declare(strict_types=1);

namespace App\Campaigning\Input;

use OpenApi\Attributes as OA;
use Prospecter\Core\Campaigns\Application\Campaign\PrepareCampaign\PrepareCampaign;
use Prospecter\Core\Campaigns\Application\Campaign\ReviseCampaign\ReviseCampaign;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A campaign as the member leaves it, prepared or revised as a whole. The core decides what makes a valid campaign (ADR
 * 0144).
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
        #[OA\Property(description: 'The chosen tags. The channel\'s tag counts with them within the target\'s 5 tags', type: 'array', items: new OA\Items(type: 'string'))]
        public ?array $tags = null,
        #[Assert\NotNull]
        #[OA\Property(description: 'The message shown for each contact, with the variables {title}, {firstName}, {lastName}, {organization} and {jobTitle}')]
        public ?string $message = null,
        #[OA\Property(description: 'An e-mail\'s subject')]
        public ?string $subject = null,
        #[OA\Property(description: 'How an AI running the campaign treats the message for each person: it sends it as rendered, or adapts it to them and keeps its idea. `asIs` when left out', enum: ['asIs', 'adapted'])]
        public ?string $messageMode = null,
        #[OA\Property(description: 'The most recipients processed in a day, only a guard; no cap when left out. Read only when the campaign is prepared: afterwards, it changes through its own endpoint')]
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
