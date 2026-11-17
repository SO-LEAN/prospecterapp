<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultContactTags;

final readonly class InheritedTagResponse
{
    /**
     * @param list<string> $from the identities of the organizations it comes from, in their order
     */
    public function __construct(
        public string $name,
        public array $from,
    ) {
    }
}
