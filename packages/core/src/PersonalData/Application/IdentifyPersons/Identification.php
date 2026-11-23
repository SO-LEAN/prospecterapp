<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\IdentifyPersons;

/**
 * What a person's clues designate. They designate one of the account's contacts (known), or none (new). They may
 * also designate several contacts, or some by their name only (to confirm, among the candidates).
 */
final readonly class Identification
{
    public const string KNOWN = 'known';
    public const string NEW = 'new';
    public const string TO_CONFIRM = 'toConfirm';

    /**
     * @param list<Candidate> $candidates
     */
    private function __construct(
        public string $status,
        public array $candidates,
    ) {
    }

    public static function known(Candidate $contact): self
    {
        return new self(self::KNOWN, [$contact]);
    }

    public static function new(): self
    {
        return new self(self::NEW, []);
    }

    /**
     * @param list<Candidate> $candidates
     */
    public static function toConfirm(array $candidates): self
    {
        return new self(self::TO_CONFIRM, $candidates);
    }
}
