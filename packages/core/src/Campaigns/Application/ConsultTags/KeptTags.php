<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultTags;

final class KeptTags implements ConsultTagsOutput
{
    /** @var list<string> */
    public private(set) array $tags = [];

    public function present(array $tags): void
    {
        $this->tags = $tags;
    }
}
