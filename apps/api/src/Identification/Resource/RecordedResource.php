<?php

declare(strict_types=1);

namespace App\Identification\Resource;

/**
 * What a recording did with a contact or an organization (ADR 0177). The status is `created`, `known` (found, and its
 * empty fields completed) or `toConfirm` (candidates for the member to choose from). A `known` one also lists the
 * fields it filled, and the fields given with a value other than the logged one. Those fields stay as they are. A call
 * with one side to confirm writes nothing. The other side is then `known`, or `new` and created by the next call.
 */
final readonly class RecordedResource
{
    public const string CREATED = 'created';
    public const string KNOWN = 'known';
    public const string NEW = 'new';
    public const string TO_CONFIRM = 'toConfirm';

    /**
     * @param list<CandidateResource> $candidates
     * @param list<string>            $completed  the names of the filled fields
     * @param list<string>            $differing  the names of the fields given with a value other than the logged one
     */
    public function __construct(
        public string $status,
        public ?string $id = null,
        public array $candidates = [],
        public array $completed = [],
        public array $differing = [],
    ) {
    }
}
