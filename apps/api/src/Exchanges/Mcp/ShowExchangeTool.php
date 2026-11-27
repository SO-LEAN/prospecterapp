<?php

declare(strict_types=1);

namespace App\Exchanges\Mcp;

use App\Exchanges\ExchangeComposition;
use App\Exchanges\ExchangeResolver;
use App\Exchanges\ExchangeVoter;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/exchanges/{id}`, for an AI (ADR 0163).
 */
#[Adapts('api_exchanges_show')]
final readonly class ShowExchangeTool
{
    public function __construct(
        private ToolAccess $access,
        private ExchangeResolver $exchanges,
        private ExchangeComposition $exchangeComposition,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string $id the exchange's id
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get-exchange', description: 'Get an exchange\'s detail. Returns its channel, direction, moment, status, report, interlocutor, attachments and campaign.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $member = $this->access->member();
        $exchange = $this->exchanges->kept($member, $id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(ExchangeVoter::ACCESS, $exchange);

        return $this->results->of($this->exchangeComposition->ofKept($member->memberId(), $exchange));
    }
}
