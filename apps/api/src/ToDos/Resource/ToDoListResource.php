<?php

declare(strict_types=1);

namespace App\ToDos\Resource;

use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * All the to-dos of a contact, an organization or the signed-in member. Those still to do come first, the soonest
 * due first. On a given day, a to-do without an hour comes before those with one. Those done follow, the last done
 * first.
 */
final readonly class ToDoListResource
{
    /**
     * @param list<ToDoResource> $toDos
     */
    public function __construct(
        public array $toDos,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public ToDoListLinksResource $links,
    ) {
    }
}
