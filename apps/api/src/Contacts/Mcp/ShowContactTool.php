<?php

declare(strict_types=1);

namespace App\Contacts\Mcp;

use App\Contacts\ContactComposition;
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
    public function __construct(private ToolAccess $access, private ContactComposition $contactComposition, private ToolResults $results)
    {
    }

    /**
     * @param string $id the contact's id
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get-contact', description: 'Get one of the account\'s contacts: their name, title, job title and contact details, and the links to their exchanges, to-dos and tags. The contact details are unmasked with the right to manage personal data, and masked otherwise.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');

        return $this->results->of($this->contactComposition->read($this->access->member()->memberId(), $id));
    }
}
