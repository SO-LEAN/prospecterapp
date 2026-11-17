<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultContactTags;

final class KeptContactTags implements ConsultContactTagsOutput
{
    /** @var list<ContactTagsResponse> */
    public private(set) array $contacts = [];

    public function present(array $contacts): void
    {
        $this->contacts = $contacts;
    }
}
