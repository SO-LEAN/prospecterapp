<?php

namespace App\Exchanges\Mcp;

use App\Exchanges\Input\VenueCorrectionInput;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\CorrectExchangeVenue\CorrectExchangeVenue;

/**
 * `PATCH /api/exchanges/{id}/venue`, for an AI (ADR 0163).
 */
#[Adapts('api_exchanges_correct_venue')]
final readonly class CorrectExchangeVenueTool
{
    public function __construct(private ExchangeWrites $writes)
    {
    }

    /**
     * @param string      $id       the id of the exchange
     * @param string|null $place    where a meeting is held, written freely; a meeting only, removed when left out
     * @param string|null $link     the web address a video call is joined by; a video call only, removed when left out
     * @param ?int        $revision the exchange's `revision` as you read it: if it changed since, the change is refused (changedMeanwhile) and you read it again
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'set-exchange-venue', description: 'Correct where a meeting is held, or the link of a video call, planned or done; open to every prospector of the account. What is left out is removed. Answers the exchange.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, ?string $place = null, ?string $link = null, ?int $revision = null): array
    {
        return $this->writes->writeOnRevision(
            $id,
            $revision,
            new VenueCorrectionInput($place, $link),
            static fn (VenueCorrectionInput $input, ExchangeDetail $exchange, string $memberId, \DateTimeImmutable $now): CorrectExchangeVenue => new CorrectExchangeVenue(prospectorId: $memberId, id: $exchange->id, place: $input->place, link: $input->link, executedAt: $now),
        );
    }
}
