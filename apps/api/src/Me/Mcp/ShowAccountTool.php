<?php

declare(strict_types=1);

namespace App\Me\Mcp;

use App\Me\MyAccountComposition;
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
    public function __construct(private ToolAccess $access, private MyAccountComposition $myAccount, private ToolResults $results)
    {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get-my-account', description: 'Get the member\'s profile (name, contact details, picture, roles) and their account\'s organization (identity, address, logo). The answer links to what they can read from there.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(): array
    {
        $this->access->denyUnlessGranted('ROLE_USER');

        return $this->results->of($this->myAccount->read($this->access->member()->memberId()));
    }
}
