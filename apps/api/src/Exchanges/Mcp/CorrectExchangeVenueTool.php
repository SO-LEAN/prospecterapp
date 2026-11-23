<?php

declare(strict_types=1);

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
     * @param string      $id       the exchange's id
     * @param string|null $place    where a meeting is held, written freely. Only a meeting has one. Left out, it is removed
     * @param string|null $link     the web address to join a video call. Only a video call has one. Left out, it is removed
     * @param ?int        $revision the exchange's `revision` as you read it. If the exchange changed since, the change is refused (changedMeanwhile): read it again
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'set-exchange-venue', description: 'Correct a meeting\'s place or a video call\'s link, planned or done. Every prospector of the account can do it. A place or a link left out is removed. Returns the exchange.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
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
