<?php

declare(strict_types=1);

namespace App\Booking\Resource;

use App\Shared\Resource\LinkResource;

/**
 * The host can always remove one of their inactive periods.
 */
final readonly class InactivePeriodLinksResource
{
    public function __construct(
        public LinkResource $remove,
    ) {
    }
}
