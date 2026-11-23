<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\PresentMembers;

/**
 * How these persons, members of an account, present themselves to the people they give a link to (ADR 0089, 0153). It
 * asks about a set. A single person is a set of one.
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
