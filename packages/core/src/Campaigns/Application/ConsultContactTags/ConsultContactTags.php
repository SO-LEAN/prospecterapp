<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultContactTags;

final readonly class ConsultContactTags
{
    /**
     * @param list<string> $contactIds a set: a contact's card asks for one, and a contact list asks for its page
     */
    public function __construct(
        public string $memberId,
        public array $contactIds,
    ) {
    }
}
