<?php

namespace App\Tags;

use App\Tags\Resource\ContactTagsResource;
use App\Tags\Resource\InheritedTagResource;
use App\Tags\Resource\TagResource;
use Prospecter\Core\Campaigns\Application\ConsultContactTags\ConsultContactTagsOutput;
use Prospecter\Core\Campaigns\Application\ConsultContactTags\ContactTagsResponse;
use Prospecter\Core\Campaigns\Application\ConsultContactTags\InheritedTagResponse;

/**
 * Presents the tags of one contact as the API shows them, an object of the call (ADR 0085): only its own are taken
 * off.
 */
final class ContactTagsResourcePresenter implements ConsultContactTagsOutput
{
    public private(set) ContactTagsResource $resource;

    public function __construct(private readonly TagResourceFactory $tags, private readonly string $contactId)
    {
    }

    /**
     * @param list<ContactTagsResponse> $contacts the one asked
     */
    public function present(array $contacts): void
    {
        $contact = $contacts[0];

        $this->resource = new ContactTagsResource(
            array_map(fn (string $name): TagResource => $this->tags->putByHand(TagRoutes::Contact, $this->contactId, $name), $contact->own),
            array_map(static fn (InheritedTagResponse $tag): InheritedTagResource => new InheritedTagResource($tag->name, $tag->from), $contact->inherited),
            $contact->automatic,
            $this->tags->linksOf(TagRoutes::Contact, $this->contactId),
        );
    }
}
