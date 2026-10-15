<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ConsultPersons;

/**
 * The persons of a list at once, for a screen showing many of them: the unknown ones are left out.
 */
final readonly class ConsultPersons
{
    /**
     * @param list<string> $ids
     */
    public function __construct(
        public string $readerId,
        public array $ids,
    ) {
    }
}
