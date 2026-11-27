<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

/**
 * The persons a view shows: its contacts, by their identity, and the members who hold or wrote what it shows, by their
 * member identity (ADR 0217). A person that PersonalData no longer reads, or a member Accounts no longer knows, is
 * absent.
 */
final readonly class PersonsShown
{
    /**
     * @param array<string, PersonDescription> $contacts by person id
     * @param array<string, PersonDescription> $members  by member id
     */
    public function __construct(
        public array $contacts = [],
        public array $members = [],
    ) {
    }
}
