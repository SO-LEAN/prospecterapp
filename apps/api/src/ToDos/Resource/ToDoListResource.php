<?php

namespace App\ToDos\Resource;

use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * The to-dos of a contact, of an organization or of the signed-in member, read whole: those to do, the soonest due
 * first, a day without an hour before its hours, then those done, the last done first.
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
