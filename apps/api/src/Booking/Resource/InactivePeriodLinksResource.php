<?php

declare(strict_types=1);

namespace App\Booking\Resource;

use App\Shared\Resource\LinkResource;

/**
 * The host may always remove an inactive period of theirs.
 */
final readonly class InactivePeriodLinksResource
{
    public function __construct(
        public LinkResource $remove,
    ) {
    }
}
