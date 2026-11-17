<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultContactTags;

final readonly class ConsultContactTags
{
    /**
     * @param list<string> $contactIds a set: the card of a contact asks one, a list of contacts its page
     */
    public function __construct(
        public string $memberId,
        public array $contactIds,
    ) {
    }
}
