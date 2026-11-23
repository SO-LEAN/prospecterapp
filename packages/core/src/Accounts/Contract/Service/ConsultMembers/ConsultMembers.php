<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Contract\Service\ConsultMembers;

/**
 * Asks who these members are (ADR 0089). The query takes a set of members. A single member is a set of one.
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
