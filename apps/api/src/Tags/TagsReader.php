<?php

declare(strict_types=1);

namespace App\Tags;

use App\Tags\Resource\TagsResource;
use Prospecter\Core\Campaigns\Application\ConsultOrganizationTags\ConsultOrganizationTags;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * An organization's tags, as the API shows them. A controller calls it after a command, which returns nothing, to
 * answer with the fresh resource.
 */
final readonly class TagsReader
{
    public function __construct(
        private QueryHandler $consultOrganizationTags,
        private TagResourceFactory $tags,
    ) {
    }

    public function read(string $me, string $organizationId): TagsResource
    {
        $presenter = new TagsResourcePresenter($this->tags, $organizationId);
        $this->consultOrganizationTags->__invoke(new ConsultOrganizationTags($me, $organizationId), $presenter);

        return $presenter->resource;
    }
}
