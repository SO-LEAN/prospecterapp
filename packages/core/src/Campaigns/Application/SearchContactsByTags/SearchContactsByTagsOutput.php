<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\SearchContactsByTags;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface SearchContactsByTagsOutput extends OutputPort
{
    public function present(TaggedContactPage $page): void;
}
