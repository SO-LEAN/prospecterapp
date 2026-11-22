<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\CallsTheMcpServer;

/**
 * An exchange is read with its revision. A screen or an AI sends it back with its correction: if another member
 * changed the exchange since, the correction is refused instead of erasing theirs (ADR 0187).
 */
#[Group('api')]
class ExchangeRevisionApiTest extends ApiTestCase
{
    use CallsTheMcpServer;

    private string $ada;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
        $acme = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Acme']]), 'id');
        $this->ada = self::stringAt($this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada'], 'organizationIds' => [$acme]]), 'id');
    }

    public function testAnExchangeIsReadWithItsRevision(): void
    {
        $exchange = $this->requestJson('GET', '/api/exchanges/'.$this->aCall());

        $this->assertSame(1, $exchange['revision']);
    }

    public function testTheTextOfAnExchangeChangedSinceItWasReadIsNotCorrectedAndTheOtherChangeIsKept(): void
    {
        $call = $this->aCall();
        $read = $this->changedSinceRead($call);

        $problem = $this->requestJson('PATCH', '/api/exchanges/'.$call.'/text?revision='.$read, ['text' => 'Rappeler mardi']);

        $this->assertChangedMeanwhile($problem);
        $this->assertSame('Rappeler jeudi', $this->requestJson('GET', '/api/exchanges/'.$call)['text']);
    }

    public function testAnExchangeChangedSinceItWasReadIsNotCorrected(): void
    {
        $call = $this->aCall();
        $read = $this->changedSinceRead($call);

        $problem = $this->requestJson('PATCH', '/api/exchanges/'.$call.'/facts?revision='.$read, ['channel' => 'call', 'direction' => 'inbound', 'occurredAt' => '2026-10-14T08:00:00+00:00', 'text' => 'Rappeler mardi']);

        $this->assertChangedMeanwhile($problem);
        $exchange = $this->requestJson('GET', '/api/exchanges/'.$call);
        $this->assertSame(['outbound', 'Rappeler jeudi'], [$exchange['direction'], $exchange['text']]);
    }

    public function testTheVenueOfAMeetingChangedSinceItWasReadIsNotCorrected(): void
    {
        $meeting = $this->aMeeting();
        $read = $this->changedSinceRead($meeting);

        $problem = $this->requestJson('PATCH', '/api/exchanges/'.$meeting.'/venue?revision='.$read, ['place' => 'Les Deux Magots']);

        $this->assertChangedMeanwhile($problem);
        $this->assertSame('Café de Flore', $this->requestJson('GET', '/api/exchanges/'.$meeting)['place']);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    #[DataProvider('toolsCorrectingACall')]
    public function testEachToolCorrectingAnExchangeChangedSinceTheAIReadItIsRefused(string $tool, array $arguments): void
    {
        $call = $this->aCall();
        $read = $this->changedSinceRead($call);

        $refusal = $this->callWithoutHandshake($tool, ['id' => $call, ...$arguments, 'revision' => $read]);

        $this->assertSame('changedMeanwhile', $this->answerOf($refusal)['reason'] ?? null);
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function toolsCorrectingACall(): iterable
    {
        yield 'text' => ['set-exchange-text', ['text' => 'Rappeler mardi']];
        yield 'facts' => ['update-exchange', ['channel' => 'call', 'direction' => 'inbound', 'occurredAt' => '2026-10-14T08:00:00+00:00']];
    }

    public function testTheToolCorrectingTheVenueOfAMeetingChangedSinceTheAIReadItIsRefused(): void
    {
        $meeting = $this->aMeeting();
        $read = $this->changedSinceRead($meeting);

        $refusal = $this->callWithoutHandshake('set-exchange-venue', ['id' => $meeting, 'place' => 'Les Deux Magots', 'revision' => $read]);

        $this->assertSame('changedMeanwhile', $this->answerOf($refusal)['reason'] ?? null);
    }

    private function aMeeting(): string
    {
        return self::stringAt($this->requestJson('POST', '/api/contacts/'.$this->ada.'/upcoming-exchanges', ['channel' => 'meeting', 'occurredAt' => '2026-10-20T09:00:00+00:00', 'place' => 'Café de Flore']), 'items', 0, 'id');
    }

    /**
     * @return int the revision read before another member changed the exchange
     */
    private function changedSinceRead(string $exchange): int
    {
        $read = self::intAt($this->requestJson('GET', '/api/exchanges/'.$exchange), 'revision');
        $this->requestJson('PATCH', '/api/exchanges/'.$exchange.'/text', ['text' => 'Rappeler jeudi']);

        return $read;
    }

    /**
     * @param array<string, mixed> $problem
     */
    private function assertChangedMeanwhile(array $problem): void
    {
        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('changedMeanwhile', $problem['reason']);
    }

    private function aCall(): string
    {
        return self::stringAt($this->requestJson('POST', '/api/contacts/'.$this->ada.'/exchanges', ['channel' => 'call', 'direction' => 'outbound', 'occurredAt' => '2026-10-14T08:00:00+00:00', 'text' => 'Rappeler lundi']), 'items', 0, 'id');
    }
}
