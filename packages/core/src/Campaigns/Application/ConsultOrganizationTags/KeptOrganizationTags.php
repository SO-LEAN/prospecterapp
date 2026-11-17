<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultOrganizationTags;

final class KeptOrganizationTags implements ConsultOrganizationTagsOutput
{
    /** @var list<string> */
    public private(set) array $tags = [];

    /** @var list<string> */
    public private(set) array $automatic = [];

    public function present(array $tags, array $automatic): void
    {
        $this->tags = $tags;
        $this->automatic = $automatic;
    }
}
