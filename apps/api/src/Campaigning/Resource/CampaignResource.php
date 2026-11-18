<?php

namespace App\Campaigning\Resource;

use App\Exchanges\Resource\AuthorResource;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * A campaign of the team (ADR 0144): Prospecter sends nothing, the members contact each person it targets themselves.
 */
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
        #[OA\Property(description: 'The tags chosen', type: 'array', items: new OA\Items(type: 'string'))]
        public array $tags,
        #[OA\Property(description: 'The tags a contact carries to be targeted: those chosen and that of the channel', type: 'array', items: new OA\Items(type: 'string'))]
        public array $targetedTags,
        #[OA\Property(description: 'The message, its variables {civilité}, {prénom}, {nom}, {organisation} and {fonction} not rendered')]
        public string $message,
        #[OA\Property(description: 'The subject of an e-mail')]
        public ?string $subject,
        #[OA\Property(enum: ['draft', 'frozen', 'started', 'stopped', 'ended'])]
        public string $status,
        #[OA\Property(description: 'Who prepared it')]
        public AuthorResource $author,
        public CampaignProgressResource $progress,
        #[OA\Property(description: 'How many contacts it targets: those its search finds now while a draft, its recipients once frozen')]
        public int $targeted,
        #[OA\Property(description: 'When it was started, none before', format: 'date-time')]
        public ?string $startedAt,
        #[OA\Property(description: 'When it was stopped, none before', format: 'date-time')]
        public ?string $stoppedAt,
        #[OA\Property(description: 'When it ended, nobody left to handle, none before', format: 'date-time')]
        public ?string $endedAt,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public CampaignLinksResource $links,
    ) {
    }
}
