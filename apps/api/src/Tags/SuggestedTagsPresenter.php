<?php

namespace App\Tags;

use App\Tags\Resource\SuggestedTagsResource;
use App\Tags\Resource\TagFamilyResource;
use Prospecter\Core\Campaigns\Application\ConsultSuggestedTags\ConsultSuggestedTagsOutput;
use Prospecter\Core\Campaigns\Application\SuggestedTags;

/**
 * Presents the tags proposed to an account, an object of the call (ADR 0085).
 */
final class SuggestedTagsPresenter implements ConsultSuggestedTagsOutput
{
    public private(set) SuggestedTagsResource $resource;

    public function present(SuggestedTags $suggested): void
    {
        $families = [];

        foreach ($suggested->predefined as $family => $tags) {
            $families[] = new TagFamilyResource($family, $tags);
        }

        $this->resource = new SuggestedTagsResource($families, $suggested->own, $suggested->automatic);
    }
}
