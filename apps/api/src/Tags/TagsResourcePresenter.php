<?php

declare(strict_types=1);

namespace App\Tags;

use App\Tags\Resource\TagResource;
use App\Tags\Resource\TagsResource;
use Prospecter\Core\Campaigns\Application\Tagging\ConsultOrganizationTags\ConsultOrganizationTagsOutput;

/** Presents an organization's tags as the API shows them. The presenter is an object of the call (ADR 0085). */
final class TagsResourcePresenter implements ConsultOrganizationTagsOutput
{
    public private(set) TagsResource $resource;

    public function __construct(private readonly TagResourceFactory $tags, private readonly string $organizationId)
    {
    }

    /**
     * @param list<string> $tags
     * @param list<string> $automatic
     */
    public function present(array $tags, array $automatic): void
    {
        $this->resource = new TagsResource(
            array_map(fn (string $name): TagResource => $this->tags->putByHand(TagRoutes::Organization, $this->organizationId, $name), $tags),
            $automatic,
            $this->tags->linksOf(TagRoutes::Organization, $this->organizationId),
        );
    }
}
