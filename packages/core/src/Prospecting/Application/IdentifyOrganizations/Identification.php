<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\IdentifyOrganizations;

/**
 * What an organization's clues designate. They designate one organization of the account (known) or none (new).
 * They can also designate several, or some by their name only. The member must confirm them among the candidates.
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

    public static function known(Candidate $organization): self
    {
        return new self(self::KNOWN, [$organization]);
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
