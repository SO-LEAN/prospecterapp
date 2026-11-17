<?php

namespace App\Tags;

use App\Tags\Resource\TagResource;
use App\Tags\Resource\TagsResource;
use Prospecter\Core\Campaigns\Application\ConsultOrganizationTags\ConsultOrganizationTagsOutput;

/**
 * Presents the tags of an organization as the API shows them, an object of the call (ADR 0085).
 */
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
