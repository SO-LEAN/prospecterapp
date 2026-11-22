<?php

declare(strict_types=1);

namespace App\Exchanges\Mcp;

use App\Exchanges\Input\TextCorrectionInput;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\CorrectExchangeText\CorrectExchangeText;

/**
 * `PATCH /api/exchanges/{id}/text`, for an AI (ADR 0163).
 */
#[Adapts('api_exchanges_correct_text')]
final readonly class CorrectExchangeTextTool
{
    public function __construct(private ExchangeWrites $writes)
    {
    }

    /**
     * @param string      $id       the id of the exchange
     * @param string|null $text     its text corrected, 5000 characters at most
     * @param bool        $redacted declares the exchange redacted, its contact forgotten; only while its link redact is offered
     * @param ?int        $revision the exchange's `revision` as you read it: if it changed since, the change is refused (changedMeanwhile) and you read it again
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'set-exchange-text', description: 'Correct the text of an exchange only, its other facts staying, to redact it once its contact was forgotten; open to every prospector of the account. Answers the exchange.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, ?string $text = null, bool $redacted = false, ?int $revision = null): array
    {
        return $this->writes->writeOnRevision(
            $id,
            $revision,
            new TextCorrectionInput($text, $redacted),
            static fn (TextCorrectionInput $input, ExchangeDetail $exchange, string $memberId, \DateTimeImmutable $now): CorrectExchangeText => new CorrectExchangeText(prospectorId: $memberId, id: $exchange->id, text: $input->text, executedAt: $now, madeConform: $input->redacted),
        );
    }
}
