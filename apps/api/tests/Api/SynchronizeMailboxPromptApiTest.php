<?php

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

        $this->assertSame('Synchroniser ma boîte e-mail', $prompt['title'] ?? null);
        $this->assertSame([['since', false]], self::argumentsOf($prompt));
    }

    public function testOnlyTheCorrespondentsProspecterKnowsAreKeptUnlessTheMemberAsksToCreateThem(): void
    {
        $this->login();

        $text = $this->promptText('sync-mailbox', ['since' => '7 jours']);

        $this->assertStringContainsString('`identify`', $text);
        $this->assertStringContainsString('Ne garde que les correspondants `known`', $text);
        $this->assertStringContainsString('`record-contact`', $text);
    }

    public function testEachEmailIsNotedWithItsMessageId(): void
    {
        $this->login();

        $text = $this->promptText('sync-mailbox', ['since' => '7 jours']);

        $this->assertStringContainsString('`log-contact-exchange`', $text);
        $this->assertStringContainsString('`messageId`', $text);
    }

    public function testASynchronizationPlayedAgainEndsOnAReportOfWhatWasAlreadyNoted(): void
    {
        $this->login();

        $text = $this->promptText('sync-mailbox', ['since' => '7 jours']);

        $this->assertStringContainsString('`alreadyNoted` et `erasedBefore` comptent comme déjà notés', $text);
    }

    public function testTheAiStopsAndAsksWhenProspecterRefuses(): void
    {
        $this->login();

        $text = $this->promptText('sync-mailbox', ['since' => '7 jours']);

        $this->assertStringContainsString('Si Prospecter refuse un appel, arrête-toi et demande-moi', $text);
    }

    public function testTheAiCreatesNothingToConfirmWithoutTheAnswerOfItsMember(): void
    {
        $this->login();

        $text = $this->promptText('sync-mailbox', ['since' => '7 jours']);

        $this->assertStringContainsString('Ne crée jamais une personne ou une entreprise `toConfirm` sans ma réponse', $text);
    }

    public function testASynchronizationPlayedAgainCountsOnlyWhatWasAlreadyNotedOrIgnored(): void
    {
        $this->login();

        $text = $this->promptText('sync-mailbox', ['since' => '7 jours']);

        $this->assertStringContainsString('Une synchronisation relancée ne doit compter que des déjà notés et des ignorés', $text);
    }

    public function testTheAiNotesNoEmailWithoutItsMessageId(): void
    {
        $this->login();

        $text = $this->promptText('sync-mailbox', ['since' => '7 jours']);

        $this->assertStringContainsString('Ne note jamais un e-mail sans son Message-ID', $text);
    }

    public function testEveryToolThePromptNamesIsServed(): void
    {
        $this->login();

        $text = $this->promptText('sync-mailbox', ['since' => '7 jours']);

        $this->assertSame([], array_diff(self::toolsNamedIn($text), array_column($this->everyToolListed(), 'name')));
    }

    public function testTheAiReadsTheMailboxSinceTheMomentGiven(): void
    {
        $this->login();

        $text = $this->promptText('sync-mailbox', ['since' => 'le 1er novembre']);

        $this->assertStringContainsString('ma boîte e-mail depuis le 1er novembre', $text);
    }

    public function testTheAiAsksSinceWhenWhenItIsNotGiven(): void
    {
        $this->login();

        $text = $this->promptText('sync-mailbox');

        $this->assertStringContainsString('ma boîte e-mail, en me demandant d\'abord depuis quand', $text);
    }
}
