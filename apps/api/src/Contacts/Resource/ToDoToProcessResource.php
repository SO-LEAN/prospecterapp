<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use App\ToDos\Resource\ToDoResource;
use OpenApi\Attributes as OA;

/**
 * A to-do to process, and how many days ago it was due.
 */
final readonly class ToDoToProcessResource
{
    public function __construct(
        public ToDoResource $toDo,
        #[OA\Property(description: 'The days since its day, on the clock of the reader: 0 for a to-do of the day', minimum: 0)]
        public int $daysOverdue,
    ) {
    }
}
