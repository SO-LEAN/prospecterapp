<?php

namespace App\Contacts\Resource;

use App\ToDos\Resource\ToDoResource;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * The exchanges the signed-in member holds in a period, planned or noted by them, whatever their status, by their
 * moment, each with who it is with; their to-dos due on a day of the period, done or not, by their due (ADR 0134);
 * what is late; the search of the contacts to plan with in a free slot when they may plan.
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
        #[OA\Property(description: 'The to-dos the member holds due on a day of the period, done or not, by their due as written: a day without an hour before its hours; none without the right to manage personal data')]
        public array $toDos,
        #[OA\Property(description: 'The ids of the exchanges and of the to-dos late at the moment of the reading, on the clock of the reader: a planned exchange whose slot has ended, a to-do not done whose hour, or day, has passed')]
        public array $late,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public CalendarLinksResource $links,
    ) {
    }
}
