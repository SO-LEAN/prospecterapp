<?php

declare(strict_types=1);

namespace App\Tags;

use App\Shared\Links;
use App\Tags\Resource\ContactTagsResource;
use App\Tags\Resource\InheritedTagResource;
use App\Tags\Resource\TagLinksResource;
use App\Tags\Resource\TagResource;
use App\Tags\Resource\TagsLinksResource;
use Prospecter\Core\Campaigns\Application\Tagging\ConsultContactTags\ConsultContactTagsOutput;
use Prospecter\Core\Campaigns\Application\Tagging\ConsultContactTags\ContactTagsResponse;
use Prospecter\Core\Campaigns\Application\Tagging\ConsultContactTags\InheritedTagResponse;

/**
 * Presents one contact's tags as the API shows them. The presenter is an object of the call (ADR 0085). Only the
 * contact's own tags can be taken off, each by its name without its `#`.
 */
final class ContactTagsResourcePresenter implements ConsultContactTagsOutput
{
    private const string MARK = '#';

    public private(set) ContactTagsResource $resource;

    public function __construct(private readonly Links $links, private readonly string $contactId)
    {
    }

    /**
     * @param list<ContactTagsResponse> $contacts the requested contact
     */
    public function present(array $contacts): void
    {
        $contact = $contacts[0];
        $id = ['id' => $this->contactId];

        $this->resource = new ContactTagsResource(
            array_map(fn (string $name): TagResource => new TagResource($name, new TagLinksResource($this->links->to('api_contacts_take_off_tag', [...$id, 'tag' => ltrim($name, self::MARK)]))), $contact->own),
            array_map(static fn (InheritedTagResponse $tag): InheritedTagResource => new InheritedTagResource($tag->name, $tag->from), $contact->inherited),
            $contact->automatic,
            new TagsLinksResource($this->links->to('api_contacts_tags', $id), $this->links->to('api_contacts_tag', $id)),
        );
    }
}
