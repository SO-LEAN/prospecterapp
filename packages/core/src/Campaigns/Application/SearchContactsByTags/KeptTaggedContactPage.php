<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\SearchContactsByTags;

final class KeptTaggedContactPage implements SearchContactsByTagsOutput
{
    public private(set) ?TaggedContactPage $page = null;

    public function present(TaggedContactPage $page): void
    {
        $this->page = $page;
    }
}
