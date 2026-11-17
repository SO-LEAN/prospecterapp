<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultOrganizationTags;

final class KeptOrganizationTags implements ConsultOrganizationTagsOutput
{
    /** @var list<string> */
    public private(set) array $tags = [];

    public function present(array $tags): void
    {
        $this->tags = $tags;
    }
}
