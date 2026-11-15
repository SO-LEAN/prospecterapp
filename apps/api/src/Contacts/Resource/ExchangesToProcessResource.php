<?php

namespace App\Contacts\Resource;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The exchanges the signed-in member planned whose end has passed and that they have not handled yet, the oldest
 * first, each with who it is with and their organization, and the links handling it.
 */
final readonly class ExchangesToProcessResource
{
    /**
     * @param list<DayExchangeResource> $items
     */
    public function __construct(
        public array $items,
        #[SerializedName('_links')]
        public ExchangesToProcessLinksResource $links,
    ) {
    }
}
