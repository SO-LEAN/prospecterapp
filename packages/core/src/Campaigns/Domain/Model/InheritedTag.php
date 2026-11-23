<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/** A tag that a contact carries because organizations that name it carry the tag by hand. */
final readonly class InheritedTag
{
    /**
     * @param list<OrganizationId> $from sorted by id
     */
    public function __construct(public Tag $tag, public array $from)
    {
    }
}
