<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

/** The organization or contact does not carry this tag, so there is nothing to take off. */
final class TagNotCarried extends NotFound
{
    public static function named(Tag $tag): self
    {
        return new self(sprintf('The tag %s is not carried', $tag));
    }
}
