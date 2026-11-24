<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Person\ConsultPersons;

/**
 * Gets the persons of a list at once, for a screen that shows many of them. It leaves out the unknown ones.
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
