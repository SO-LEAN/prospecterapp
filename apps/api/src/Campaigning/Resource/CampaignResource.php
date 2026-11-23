<?php

declare(strict_types=1);

namespace App\Campaigning\Resource;

use App\Exchanges\Resource\AuthorResource;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/** A team campaign (ADR 0144). Prospecter sends nothing: the members contact each person it targets themselves. */
final readonly class CampaignResource
{
    /**
     * @param list<string> $tags
     * @param list<string> $targetedTags
     */
    public function __construct(
        public string $id,
        public string $name,
        #[OA\Property(enum: ['email', 'call', 'sms', 'linkedIn'])]
        public string $channel,
        #[OA\Property(description: 'The chosen tags', type: 'array', items: new OA\Items(type: 'string'))]
        public array $tags,
        #[OA\Property(description: 'The tags a targeted contact carries: the chosen tags and the channel\'s tag', type: 'array', items: new OA\Items(type: 'string'))]
        public array $targetedTags,
        #[OA\Property(description: 'The message, with its variables {title}, {firstName}, {lastName}, {organization} and {jobTitle} not rendered')]
        public string $message,
        #[OA\Property(description: 'An e-mail\'s subject')]
        public ?string $subject,
        #[OA\Property(description: 'How an AI running the campaign treats the message for each person: it sends it as rendered, or adapts it to them and keeps its idea', enum: ['asIs', 'adapted'])]
        public string $messageMode,
        #[OA\Property(enum: ['draft', 'frozen', 'started', 'stopped', 'ended'])]
        public string $status,
        #[OA\Property(description: 'Who prepared it')]
        public AuthorResource $author,
        public CampaignProgressResource $progress,
        #[OA\Property(description: 'How many contacts it targets: a draft counts what its search finds now, and a frozen campaign counts its recipients')]
        public int $targeted,
        #[OA\Property(description: 'When it was started, null before', format: 'date-time')]
        public ?string $startedAt,
        #[OA\Property(description: 'When it was stopped, null before', format: 'date-time')]
        public ?string $stoppedAt,
        #[OA\Property(description: 'When it ended because nobody was left to handle, null before', format: 'date-time')]
        public ?string $endedAt,
        #[OA\Property(description: 'The most recipients processed in a day. It is a guard: an AI stops at it, and the member may go beyond it. Null without a cap')]
        public ?int $dailyCap,
        #[OA\Property(description: 'Its recipients processed today on the reader\'s clock, when the campaign is read alone; null in a list')]
        public ?int $processedToday,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public CampaignLinksResource $links,
    ) {
    }
}
