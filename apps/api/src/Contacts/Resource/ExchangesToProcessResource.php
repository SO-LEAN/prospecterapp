<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The exchanges the signed-in member planned whose end has passed and that they have not handled yet, the oldest
 * first. Each one comes with who it is with, their organization and the links that handle it. Then come the
 * member's open to-dos due on the day of the reading or before, the most overdue first.
 */
final readonly class ExchangesToProcessResource
{
    /**
     * @param list<DayExchangeResource>     $items
     * @param list<ToDoToProcessResource>   $toDos
     * @param list<ContactToAttachResource> $contactsToAttach
     */
    public function __construct(
        public array $items,
        #[OA\Property(description: 'The open to-dos due on the day of the reading, on the reader\'s clock, or on the days before, the most overdue first. There are none without the right to manage personal data')]
        public array $toDos,
        #[OA\Property(description: 'The contacts the member met who wait for the member to attach them to an organization. The first to be erased comes first (ADR 0146)')]
        public array $contactsToAttach,
        #[SerializedName('_links')]
        public ExchangesToProcessLinksResource $links,
    ) {
    }
}
