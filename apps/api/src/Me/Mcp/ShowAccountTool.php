<?php

namespace App\Me\Mcp;

use App\Me\MyAccountReader;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/me`, for an AI (ADR 0163).
 */
#[Adapts('api_me_show')]
final readonly class ShowAccountTool
{
    public function __construct(private ToolAccess $access, private MyAccountReader $myAccount, private ToolResults $results)
    {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get-my-account', description: 'The profile of the member (name, contact details, picture, roles) and the organization of their account (identity, address, logo), with the links to what they read there.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(): array
    {
        $this->access->denyUnlessGranted('ROLE_USER');

        return $this->results->of($this->myAccount->read($this->access->member()->memberId()));
    }
}
