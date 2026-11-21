<?php

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
     * @param string      $id          the id of the exchange
     * @param string|null $text        its text corrected, 5000 characters at most
     * @param bool        $madeConform declares the exchange made conform, its contact forgotten; only while its link makeConform is offered
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'set-exchange-text', description: 'Correct the text of an exchange only, its other facts staying, to make it conform once its contact was forgotten; open to every prospector of the account. Answers the exchange.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, ?string $text = null, bool $madeConform = false): array
    {
        return $this->writes->write(
            $id,
            new TextCorrectionInput($text, $madeConform),
            static fn (TextCorrectionInput $input, ExchangeDetail $exchange, string $memberId, \DateTimeImmutable $now): CorrectExchangeText => new CorrectExchangeText($memberId, $exchange->id, $input->text, $now, $input->madeConform),
        );
    }
}
