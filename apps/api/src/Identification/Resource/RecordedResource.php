<?php

declare(strict_types=1);

namespace App\Identification\Resource;

/**
 * What a recording did of a contact or an organization (ADR 0177): `created`, `known` (found, completed with what was
 * empty) or `toConfirm` (its candidates, for the member to choose), and, when known, the fields it filled and those it
 * was given otherwise than noted, which stay as they are. A call with one to confirm writes nothing: the other is
 * then `known` or `new`, created by the next call.
 */
final readonly class RecordedResource
{
    public const string CREATED = 'created';
    public const string KNOWN = 'known';
    public const string NEW = 'new';
    public const string TO_CONFIRM = 'toConfirm';

    /**
     * @param list<CandidateResource> $candidates
     * @param list<string>            $completed  the names of the fields filled
     * @param list<string>            $differing  the names of the fields given otherwise than noted
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
