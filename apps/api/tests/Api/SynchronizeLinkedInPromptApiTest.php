<?php

declare(strict_types=1);

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

        $prompt = $this->promptListed('sync-linkedin');

        $this->assertSame(['Sync LinkedIn', 'Record your relevant LinkedIn contacts in Prospecter, or log your LinkedIn messages, without duplicates.'], [$prompt['title'] ?? null, $prompt['description'] ?? null]);
        $this->assertSame([['what', true], ['since', false]], self::argumentsOf($prompt));
    }

    public function testTheAiTellsItsMemberWhatEachArgumentMeans(): void
    {
        $this->login();

        $prompt = $this->promptListed('sync-linkedin');

        $this->assertSame(['contacts or messages', 'how far back to go, in quotes ("7 days", "November 1"…), asked when not given'], array_column(self::records($prompt['arguments'] ?? []), 'description'));
    }

    public function testEachMessageIsNotedWithItsConversationAndItsMinute(): void
    {
        $this->login();

        $text = $this->promptText('sync-linkedin', ['what' => 'messages', 'since' => '7 days']);

        $this->assertStringContainsString('`log-contact-exchange`', $text);
        $this->assertStringContainsString('`linkedInConversation`', $text);
        $this->assertStringContainsString('`occurredAt`', $text);
    }

    public function testTheContactsAreRecordedWithoutNotingAnyMessage(): void
    {
        $this->login();

        $text = $this->promptText('sync-linkedin', ['what' => 'contacts', 'since' => '7 days']);

        $this->assertStringContainsString('`record-contact`', $text);
        $this->assertStringNotContainsString('`log-contact-exchange`', $text);
    }

    public function testTheAiIdentifiesThePersonsBeforeAndAsksItsMemberAboutThoseToConfirm(): void
    {
        $this->login();

        $text = $this->promptText('sync-linkedin', ['what' => 'contacts', 'since' => '7 days']);

        $this->assertLessThan(strpos($text, '`record-contact`'), strpos($text, '`identify`'));
        $this->assertStringContainsString('`toConfirm`', $text);
    }

    public function testTheAiGivesThePublicProfileAddressAndOpensTheProfileWhenTheConversationHidesIt(): void
    {
        $this->login();

        $text = $this->promptText('sync-linkedin', ['what' => 'messages', 'since' => '7 days']);

        $this->assertStringContainsString("Give each person's public profile address (`linkedin.com/in/<name>/`), never the member ID a conversation shows (`linkedin.com/in/ACoAA…`). When you only have that ID, open the profile and read the public address there.", $text);
    }

    public function testTheAiReadsLinkedInSinceTheMomentGiven(): void
    {
        $this->login();

        $text = $this->promptText('sync-linkedin', ['what' => 'messages', 'since' => 'November 1']);

        $this->assertStringContainsString('Sync my LinkedIn messages into Prospecter. How far back: November 1.', $text);
    }

    public function testTheAiAsksSinceWhenWhenTheMomentGivenIsUnclear(): void
    {
        $this->login();

        $text = $this->promptText('sync-linkedin', ['what' => 'messages', 'since' => '7']);

        $this->assertStringContainsString('How far back: 7. When this is not a clear moment, ask me how far back to go first. Do not guess.', $text);
    }

    public function testTheAiAsksSinceWhenWhenItIsNotGiven(): void
    {
        $this->login();

        $text = $this->promptText('sync-linkedin', ['what' => 'contacts']);

        $this->assertStringContainsString('Sync my LinkedIn contacts into Prospecter. First ask me how far back to go.', $text);
    }

    public function testASynchronizationPlayedAgainEndsOnAReportOfWhatWasAlreadyNoted(): void
    {
        $this->login();

        $text = $this->promptText('sync-linkedin', ['what' => 'messages', 'since' => '7 days']);

        $this->assertStringContainsString('`alreadyNoted` and `erasedBefore` count as already logged', $text);
        $this->assertStringContainsString('created, completed, already logged, ignored', $text);
    }

    public function testTheAiStopsAndAsksWhenProspecterRefuses(): void
    {
        $this->login();

        $text = $this->promptText('sync-linkedin', ['what' => 'contacts', 'since' => '7 days']);

        $this->assertStringContainsString('If Prospecter refuses a call, stop and ask me', $text);
    }

    public function testTheAiCreatesNothingToConfirmWithoutTheAnswerOfItsMember(): void
    {
        $this->login();

        $text = $this->promptText('sync-linkedin', ['what' => 'contacts', 'since' => '7 days']);

        $this->assertStringContainsString('Never create a `toConfirm` person or company without my answer', $text);
    }

    public function testTheAiNotesNoMessageWithoutItsConversation(): void
    {
        $this->login();

        $text = $this->promptText('sync-linkedin', ['what' => 'messages', 'since' => '7 days']);

        $this->assertStringContainsString('Never log a message without its conversation when you know it', $text);
    }

    public function testAContactKnownWithNothingCompletedCountsAsAlreadyNoted(): void
    {
        $this->login();

        $text = $this->promptText('sync-linkedin', ['what' => 'contacts', 'since' => '7 days']);

        $this->assertStringContainsString('`known` with nothing completed counts as already logged', $text);
    }

    public function testASynchronizationPlayedAgainCountsOnlyWhatWasAlreadyNoted(): void
    {
        $this->login();

        $text = $this->promptText('sync-linkedin', ['what' => 'contacts', 'since' => '7 days']);

        $this->assertStringContainsString('A sync run again counts only items already logged', $text);
    }

    public function testABlankSinceWhenIsAskedAsWhenItIsNotGiven(): void
    {
        $this->login();

        $text = $this->promptText('sync-linkedin', ['what' => 'contacts', 'since' => '  ']);

        $this->assertStringContainsString('Sync my LinkedIn contacts into Prospecter. First ask me how far back to go.', $text);
    }

    public function testEveryToolThePromptNamesIsServed(): void
    {
        $this->login();

        $text = $this->promptText('sync-linkedin', ['what' => 'messages', 'since' => '7 days']);

        $this->assertSame([], array_diff(self::toolsNamedIn($text), array_map(static fn (array $tool): string => self::stringAt($tool, 'name'), $this->everyToolListed())));
    }

    public function testNeitherContactsNorMessagesIsRefusedAsAWrongArgument(): void
    {
        $this->login();

        $this->sendWithHandshake('prompts/get', ['name' => 'sync-linkedin', 'arguments' => ['what' => 'posts']]);

        $this->assertSame(-32602, self::valueAt($this->responseData(), 'error', 'code'));
    }
}
