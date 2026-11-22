<?php

declare(strict_types=1);

namespace App\Contacts\Mcp;

use App\Contacts\ContactReader;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/contacts/{id}`, for an AI (ADR 0163).
 */
#[Adapts('api_contacts_show')]
final readonly class ShowContactTool
{
    public function __construct(private ToolAccess $access, private ContactReader $contactReader, private ToolResults $results)
    {
    }

    /**
     * @param string $id the id of the contact
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get-contact', description: 'A contact of the account: their name, title, job title, contact details (in clear with the right to manage the personal data, masked otherwise), and the links to their exchanges, to-dos and tags.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');

        return $this->results->of($this->contactReader->read($this->access->member()->memberId(), $id));
    }
}
