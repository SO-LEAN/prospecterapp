<?php

declare(strict_types=1);

namespace App\Contacts\Mcp;

use App\Contacts\CallerReader;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/phone-numbers/{phoneNumber}`, for an AI (ADR 0163).
 */
#[Adapts('api_phone_numbers_recognize')]
final readonly class RecognizeCallerTool
{
    public function __construct(private ToolAccess $access, private CallerReader $callers, private ToolResults $results)
    {
    }

    /**
     * @param string $phoneNumber the calling number, in E.164 or in the French national format
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get-caller', description: 'Find who is calling: the account\'s organizations and contacts that have this number. It needs the right to manage personal data.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $phoneNumber): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');

        return $this->results->of($this->callers->recognize($this->access->member()->memberId(), $phoneNumber));
    }
}
