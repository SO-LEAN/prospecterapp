<?php

namespace App\Campaigning\Input;

use OpenApi\Attributes as OA;
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
        #[OA\Property(description: 'The message reminded for each contact, its variables {prénom}, {nom}, {organisation} and {fonction}')]
        public ?string $message = null,
        #[OA\Property(description: 'The subject of an e-mail')]
        public ?string $subject = null,
    ) {
    }
}
