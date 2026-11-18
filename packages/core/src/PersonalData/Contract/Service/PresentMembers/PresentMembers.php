<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\PresentMembers;

/**
 * How these persons, members of an account, present themselves to whom they give a link (ADR 0089, 0153): asked
 * about a set, a single person being a set of one.
 */
final readonly class PresentMembers
{
    /**
     * @param list<string> $personIds
     */
    public function __construct(public array $personIds)
    {
    }
}
