<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\CallsTheMcpServer;

/**
 * The AI of a member notes the e-mails of their mailbox exchanged with those Prospecter knows by a prompt of the
 * server, each once by its Message-ID, a synchronization played again counting only what was noted.
 */
#[Group('api')]
class SynchronizeMailboxPromptApiTest extends ApiTestCase
{
    use CallsTheMcpServer;

    public function testTheAiFindsThePromptWithSinceWhen(): void
    {
        $this->login();

        $prompt = $this->promptListed('sync-mailbox');

        $this->assertSame(['Sync my mailbox', 'Log the e-mails you exchanged with your contacts in Prospecter, without duplicates.'], [$prompt['title'] ?? null, $prompt['description'] ?? null]);
        $this->assertSame([['since', false]], self::argumentsOf($prompt));
        $this->assertSame(['how far back to go, in quotes ("7 days", "November 1"…), asked when not given'], array_column($prompt['arguments'] ?? [], 'description'));
    }

    public function testOnlyTheCorrespondentsProspecterKnowsAreKeptUnlessTheMemberAsksToCreateThem(): void
    {
        $this->login();

        $text = $this->promptText('sync-mailbox', ['since' => '7 days']);

        $this->assertStringContainsString('`identify`', $text);
        $this->assertStringContainsString('Keep only the `known` correspondents', $text);
        $this->assertStringContainsString('`record-contact`', $text);
    }

    public function testEachEmailIsNotedWithItsMessageId(): void
    {
        $this->login();

        $text = $this->promptText('sync-mailbox', ['since' => '7 days']);

        $this->assertStringContainsString('`log-contact-exchange`', $text);
        $this->assertStringContainsString('`messageId`', $text);
    }

    public function testASynchronizationPlayedAgainEndsOnAReportOfWhatWasAlreadyNoted(): void
    {
        $this->login();

        $text = $this->promptText('sync-mailbox', ['since' => '7 days']);

        $this->assertStringContainsString('`alreadyNoted` and `erasedBefore` count as already logged', $text);
    }

    public function testTheAiStopsAndAsksWhenProspecterRefuses(): void
    {
        $this->login();

        $text = $this->promptText('sync-mailbox', ['since' => '7 days']);

        $this->assertStringContainsString('If Prospecter refuses a call, stop and ask me', $text);
    }

    public function testTheAiCreatesNothingToConfirmWithoutTheAnswerOfItsMember(): void
    {
        $this->login();

        $text = $this->promptText('sync-mailbox', ['since' => '7 days']);

        $this->assertStringContainsString('Never create a `toConfirm` person or company without my answer', $text);
    }

    public function testASynchronizationPlayedAgainCountsOnlyWhatWasAlreadyNotedOrIgnored(): void
    {
        $this->login();

        $text = $this->promptText('sync-mailbox', ['since' => '7 days']);

        $this->assertStringContainsString('A sync run again counts only items already logged or ignored', $text);
    }

    public function testTheAiNotesNoEmailWithoutItsMessageId(): void
    {
        $this->login();

        $text = $this->promptText('sync-mailbox', ['since' => '7 days']);

        $this->assertStringContainsString('Never log an e-mail without its Message-ID', $text);
    }

    public function testEveryToolThePromptNamesIsServed(): void
    {
        $this->login();

        $text = $this->promptText('sync-mailbox', ['since' => '7 days']);

        $this->assertSame([], array_diff(self::toolsNamedIn($text), array_column($this->everyToolListed(), 'name')));
    }

    public function testTheAiReadsTheMailboxSinceTheMomentGiven(): void
    {
        $this->login();

        $text = $this->promptText('sync-mailbox', ['since' => 'November 1']);

        $this->assertStringContainsString('Sync my mailbox into Prospecter. How far back: November 1.', $text);
    }

    public function testTheAiAsksSinceWhenWhenTheMomentGivenIsUnclear(): void
    {
        $this->login();

        $text = $this->promptText('sync-mailbox', ['since' => '7']);

        $this->assertStringContainsString('How far back: 7. When this is not a clear moment, ask me how far back to go first. Do not guess.', $text);
    }

    public function testTheAiAsksSinceWhenWhenItIsNotGiven(): void
    {
        $this->login();

        $text = $this->promptText('sync-mailbox');

        $this->assertStringContainsString('Sync my mailbox into Prospecter. First ask me how far back to go.', $text);
    }
}
