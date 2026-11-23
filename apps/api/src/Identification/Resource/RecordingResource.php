<?php

declare(strict_types=1);

namespace App\Identification\Resource;

/**
 * What a recording did with the organization, then with the contact (ADR 0177).
 */
final readonly class RecordingResource
{
    public function __construct(
        public RecordedResource $organization,
        public RecordedResource $contact,
    ) {
    }
}
