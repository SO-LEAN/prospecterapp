<?php

namespace App\Contacts\Resource;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The exchanges the signed-in member planned whose end has passed and that they have not handled yet, the oldest
 * first, each with who it is with and their organization, and the links handling it; the to-dos they still have to do
 * of the day of the reading and before, the most overdue first.
 */
final readonly class ExchangesToProcessResource
{
    /**
     * @param list<DayExchangeResource>   $items
     * @param list<ToDoToProcessResource> $toDos
     */
    public function __construct(
        public array $items,
        #[OA\Property(description: 'The to-dos still to do of the day of the reading, on the clock of the reader, and of the days before, the most overdue first; none without the right to manage personal data')]
        public array $toDos,
        #[SerializedName('_links')]
        public ExchangesToProcessLinksResource $links,
    ) {
    }
}
