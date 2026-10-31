<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Contract\Service\ConsultMembers;

/**
 * Who these members are (ADR 0089): asked about a set, a single member being a set of one.
 */
final readonly class ConsultMembers
{
    /**
     * @param list<string> $memberIds
     */
    public function __construct(
        public array $memberIds,
    ) {
    }
}
