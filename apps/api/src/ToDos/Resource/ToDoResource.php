<?php

declare(strict_types=1);

namespace App\ToDos\Resource;

use App\Exchanges\Resource\AuthorResource;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * A to-do for a contact or for an organization (ADR 0113).
 */
final readonly class ToDoResource
{
    public function __construct(
        public string $id,
        public string $text,
        #[OA\Property(description: 'The day it is due, `Y-m-d`', format: 'date')]
        public string $dueOn,
        #[OA\Property(description: 'The hour of that day, `H:i`, when given', example: '08:30')]
        public ?string $dueAt,
        #[OA\Property(description: 'When it was ticked off. None while it is still to do', format: 'date-time')]
        public ?string $doneAt,
        #[OA\Property(description: 'Who logged it, and so holds it')]
        public AuthorResource $heldBy,
        #[OA\Property(description: 'Its contact. None for a to-do for the organization itself')]
        public ?ToDoContactResource $contact,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public ToDoLinksResource $links,
    ) {
    }
}
