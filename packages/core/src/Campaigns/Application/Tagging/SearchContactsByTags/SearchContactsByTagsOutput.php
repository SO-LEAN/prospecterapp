<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Tagging\SearchContactsByTags;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface SearchContactsByTagsOutput extends OutputPort
{
    public function present(TaggedContactPage $page): void;
}
