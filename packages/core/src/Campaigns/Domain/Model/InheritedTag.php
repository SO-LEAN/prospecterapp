<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * A tag a contact carries because organizations naming it carry it by hand.
 */
final readonly class InheritedTag
{
    /**
     * @param list<OrganizationId> $from in the order of their identities
     */
    public function __construct(public Tag $tag, public array $from)
    {
    }
}
