<?php

declare(strict_types=1);

namespace App\ToDos\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;
use OpenApi\Attributes as OA;

final readonly class ToDoLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(description: 'Change its text and when it is due', nullable: false)]
        #[Offers('api_to_dos_revise')]
        public ?LinkResource $revise = null,
        #[OA\Property(description: 'Tick it off, while it is still to do', nullable: false)]
        #[Offers('api_to_dos_tick_off')]
        public ?LinkResource $tickOff = null,
        #[OA\Property(description: 'Untick it, once done', nullable: false)]
        #[Offers('api_to_dos_untick')]
        public ?LinkResource $untick = null,
        #[OA\Property(nullable: false)]
        #[Offers('api_to_dos_erase')]
        public ?LinkResource $erase = null,
    ) {
    }
}
