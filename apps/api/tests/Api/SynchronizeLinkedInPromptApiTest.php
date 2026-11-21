<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\CallsTheMcpServer;

/**
 * The AI of a member synchronizes their contacts or their messages of LinkedIn by a prompt of the server: the steps
 * chaining the tools so that nothing is created twice, a synchronization played again counting only what was noted.
 */
#[Group('api')]
class SynchronizeLinkedInPromptApiTest extends ApiTestCase
{
    use CallsTheMcpServer;

    public function testTheAiFindsThePromptWithWhatToSynchronizeAndSinceWhen(): void
    {
        $this->login();

        $prompt = $this->promptListed('synchroniser-linkedin');

        $this->assertSame('Synchroniser LinkedIn', $prompt['title'] ?? null);
        $this->assertSame([['what', true], ['since', false]], self::argumentsOf($prompt));
    }

    public function testEachMessageIsNotedWithItsConversationAndItsMinute(): void
    {
        $this->login();

        $text = $this->promptText('synchroniser-linkedin', ['what' => 'messages', 'since' => '7 jours']);

        $this->assertStringContainsString('`noter-un-echange`', $text);
        $this->assertStringContainsString('`linkedInConversation`', $text);
        $this->assertStringContainsString('`occurredAt`', $text);
    }

    public function testTheContactsAreRecordedWithoutNotingAnyMessage(): void
    {
        $this->login();

        $text = $this->promptText('synchroniser-linkedin', ['what' => 'contacts', 'since' => '7 jours']);

        $this->assertStringContainsString('`enregistrer-un-contact`', $text);
        $this->assertStringNotContainsString('`noter-un-echange`', $text);
    }

    public function testTheAiIdentifiesThePersonsBeforeAndAsksItsMemberAboutThoseToConfirm(): void
    {
        $this->login();

        $text = $this->promptText('synchroniser-linkedin', ['what' => 'contacts', 'since' => '7 jours']);

        $this->assertLessThan(strpos($text, '`enregistrer-un-contact`'), strpos($text, '`identifier`'));
        $this->assertStringContainsString('`toConfirm`', $text);
    }

    public function testTheAiReadsLinkedInSinceTheMomentGiven(): void
    {
        $this->login();

        $text = $this->promptText('synchroniser-linkedin', ['what' => 'messages', 'since' => 'le 1er novembre']);

        $this->assertStringContainsString('mes messages LinkedIn depuis le 1er novembre', $text);
    }

    public function testTheAiAsksSinceWhenWhenItIsNotGiven(): void
    {
        $this->login();

        $text = $this->promptText('synchroniser-linkedin', ['what' => 'contacts']);

        $this->assertStringContainsString('mes contacts LinkedIn, en me demandant d\'abord depuis quand', $text);
    }

    public function testASynchronizationPlayedAgainEndsOnAReportOfWhatWasAlreadyNoted(): void
    {
        $this->login();

        $text = $this->promptText('synchroniser-linkedin', ['what' => 'messages', 'since' => '7 jours']);

        $this->assertStringContainsString('`alreadyNoted` et `erasedBefore` comptent comme déjà notés', $text);
        $this->assertStringContainsString('créés, complétés, déjà notés, ignorés', $text);
    }

    public function testTheAiStopsAndAsksWhenProspecterRefuses(): void
    {
        $this->login();

        $text = $this->promptText('synchroniser-linkedin', ['what' => 'contacts', 'since' => '7 jours']);

        $this->assertStringContainsString('Si Prospecter refuse un appel, arrête-toi et demande-moi', $text);
    }

    public function testTheAiCreatesNothingToConfirmWithoutTheAnswerOfItsMember(): void
    {
        $this->login();

        $text = $this->promptText('synchroniser-linkedin', ['what' => 'contacts', 'since' => '7 jours']);

        $this->assertStringContainsString('Ne crée jamais une personne ou une entreprise `toConfirm` sans ma réponse', $text);
    }

    public function testTheAiNotesNoMessageWithoutItsConversation(): void
    {
        $this->login();

        $text = $this->promptText('synchroniser-linkedin', ['what' => 'messages', 'since' => '7 jours']);

        $this->assertStringContainsString('Ne note jamais un message sans sa conversation quand tu la connais', $text);
    }

    public function testAContactKnownWithNothingCompletedCountsAsAlreadyNoted(): void
    {
        $this->login();

        $text = $this->promptText('synchroniser-linkedin', ['what' => 'contacts', 'since' => '7 jours']);

        $this->assertStringContainsString('`known` sans rien de complété, comme déjà noté', $text);
    }

    public function testASynchronizationPlayedAgainCountsOnlyWhatWasAlreadyNoted(): void
    {
        $this->login();

        $text = $this->promptText('synchroniser-linkedin', ['what' => 'contacts', 'since' => '7 jours']);

        $this->assertStringContainsString('Une synchronisation relancée ne doit compter que des déjà notés', $text);
    }

    public function testABlankSinceWhenIsAskedAsWhenItIsNotGiven(): void
    {
        $this->login();

        $text = $this->promptText('synchroniser-linkedin', ['what' => 'contacts', 'since' => '  ']);

        $this->assertStringContainsString('mes contacts LinkedIn, en me demandant d\'abord depuis quand', $text);
    }

    public function testEveryToolThePromptNamesIsServed(): void
    {
        $this->login();

        $text = $this->promptText('synchroniser-linkedin', ['what' => 'messages', 'since' => '7 jours']);

        $this->assertSame([], array_diff(self::toolsNamedIn($text), array_column($this->everyToolListed(), 'name')));
    }

    public function testNeitherContactsNorMessagesIsRefusedAsAWrongArgument(): void
    {
        $this->login();

        $this->sendWithHandshake('prompts/get', ['name' => 'synchroniser-linkedin', 'arguments' => ['what' => 'posts']]);

        $this->assertSame(-32602, $this->responseData()['error']['code'] ?? null);
    }
}
