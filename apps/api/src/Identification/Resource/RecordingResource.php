<?php

declare(strict_types=1);

namespace App\Identification\Resource;

/**
 * What a recording did of the organization, then of the contact (ADR 0177).
 */
final readonly class RecordingResource
{
    public function __construct(
        public RecordedResource $organization,
        public RecordedResource $contact,
    ) {
    }
}
