<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use App\ToDos\Resource\ToDoResource;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * The exchanges the signed-in member holds in a period, planned or logged by them, whatever their status. They come
 * by their moment, each with who it is with. The calendar also holds the member's to-dos due on a day of the period,
 * done or not, by their due date (ADR 0134). It says what is late. When the member may plan, it links to the search
 * for contacts to plan an exchange with in a free slot.
 */
final readonly class CalendarResource
{
    /**
     * @param list<OrganizationExchangeResource> $items
     * @param list<ToDoResource>                 $toDos
     * @param list<string>                       $late
     */
    public function __construct(
        public array $items,
        #[OA\Property(description: 'The member\'s to-dos due on a day of the period, done or not, ordered by their due date as written. A day without an hour comes before that day\'s hours. There are none without the right to manage personal data')]
        public array $toDos,
        #[OA\Property(description: 'The ids of the exchanges and to-dos that are late when read, on the reader\'s clock. A planned exchange is late when its slot has ended. A to-do is late when it is not done and its hour, or day, has passed')]
        public array $late,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public CalendarLinksResource $links,
    ) {
    }
}
