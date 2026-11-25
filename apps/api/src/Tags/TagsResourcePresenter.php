<?php

declare(strict_types=1);

namespace App\Tags;

use App\Shared\Links;
use App\Tags\Resource\TagLinksResource;
use App\Tags\Resource\TagResource;
use App\Tags\Resource\TagsLinksResource;
use App\Tags\Resource\TagsResource;
use Prospecter\Core\Campaigns\Application\Tagging\ConsultOrganizationTags\ConsultOrganizationTagsOutput;

/**
 * Presents an organization's tags as the API shows them. The presenter is an object of the call (ADR 0085). Each tag
 * put by hand is taken off by its name without its `#`.
 */
final class TagsResourcePresenter implements ConsultOrganizationTagsOutput
{
    private const string MARK = '#';

    public private(set) TagsResource $resource;

    public function __construct(private readonly Links $links, private readonly string $organizationId)
    {
    }

    /**
     * @param list<string> $tags
     * @param list<string> $automatic
     */
    public function present(array $tags, array $automatic): void
    {
        $id = ['id' => $this->organizationId];

        $this->resource = new TagsResource(
            array_map(fn (string $name): TagResource => new TagResource($name, new TagLinksResource($this->links->to('api_organizations_take_off_tag', [...$id, 'tag' => ltrim($name, self::MARK)]))), $tags),
            $automatic,
            new TagsLinksResource($this->links->to('api_organizations_tags', $id), $this->links->to('api_organizations_tag', $id)),
        );
    }
}
