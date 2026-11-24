<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Tagging\ConsultContactTags;

final readonly class InheritedTagResponse
{
    /**
     * @param list<string> $from the ids of the organizations it comes from, sorted by id
     */
    public function __construct(
        public string $name,
        public array $from,
    ) {
    }
}
