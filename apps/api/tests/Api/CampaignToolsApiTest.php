<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Symfony\Component\Clock\MockClock;
use Tests\App\Base\ActsAsItsEndpoint;
use Tests\App\Base\ApiTestCase;

/**
 * An AI prepares, freezes, starts and stops the campaigns of its member's team, as its member does on the screens
 * (ADR 0163), with the right to manage the personal data only.
 */
#[Group('api')]
class CampaignToolsApiTest extends ApiTestCase
{
    use ActsAsItsEndpoint;

    private const string TAG = '#PHP';

    private const array RELAUNCH = ['name' => 'Relance PHP', 'channel' => 'email', 'tags' => [self::TAG], 'message' => 'Bonjour {firstName}', 'subject' => 'Une mission PHP ?'];

    private const array SENT_TO_ADA = ['text' => 'Bonjour Ada', 'messageId' => '<relance-ada@solean-it.test>'];

    /** The members a campaign of the team of the prospector is written by: another account knows nothing of it. */
    private const array TEAM_WRITERS = ['prospector-user', 'admin-user'];

    /** Late on the controllers' day in UTC, and already the next day in Paris. */
    private const string LATE_THAT_DAY = '2026-10-15 23:00:00 UTC';

    private string $ada;
    private string $alan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aMemberOfTheFixtures('admin-user', Role::User, Role::Admin);
        $this->aMemberOfTheFixtures('member-user', Role::User);
        $this->aMemberOfAnotherAccount('stranger');
        $this->login();
        $acme = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']]), 'id');
        $this->requestJson('POST', '/api/organizations/'.$acme.'/tags', ['tags' => [self::TAG]]);
        $this->ada = $this->aContactOf($acme, 'Ada', 'Lovelace', 'ada@acme.test');
        $this->alan = $this->aContactOf($acme, 'Alan', 'Turing', 'alan@acme.test');
    }

    public function testTheAiPreparesACampaign(): void
    {
        $campaign = $this->assertTheToolActsAsItsEndpoint('create-campaign', self::RELAUNCH, 'POST', '/api/campaigns', self::RELAUNCH, [...self::PERSONAL_DATA_MANAGERS, 'stranger']);

        $this->assertSame(['Relance PHP', 'email', 'draft'], [$campaign['name'], $campaign['channel'], $campaign['status']]);
    }

    public function testTheAiPreparesACampaignWithItsDailyCap(): void
    {
        $this->login();

        $campaign = $this->callWithoutHandshake('create-campaign', [...self::RELAUNCH, 'dailyCap' => 30]);

        $this->assertSame(30, self::valueAt($campaign, 'structuredContent', 'dailyCap'));
    }

    public function testTheAiRevisesADraft(): void
    {
        $draft = $this->aDraft();
        $revised = [...self::RELAUNCH, 'message' => 'Bonjour {firstName}, une mission PHP vous intéresse ?'];

        $campaign = $this->assertTheToolActsAsItsEndpoint('update-campaign', ['id' => $draft, ...$revised], 'PATCH', '/api/campaigns/'.$draft, $revised, self::TEAM_WRITERS);

        $this->assertSame('Bonjour {firstName}, une mission PHP vous intéresse ?', $campaign['message']);
    }

    public function testTheAiFreezesTheRecipientsOfADraft(): void
    {
        $draft = $this->aDraft();

        $campaign = $this->assertTheToolActsAsItsEndpoint('freeze-campaign', ['id' => $draft], 'POST', '/api/campaigns/'.$draft.'/freeze', null, self::TEAM_WRITERS);

        $this->assertSame('frozen', $campaign['status']);
    }

    public function testTheAiStartsAFrozenCampaignItsRecipientsKept(): void
    {
        $frozen = $this->aFrozenCampaign();

        $campaign = $this->assertTheToolActsAsItsEndpoint('start-campaign', ['id' => $frozen], 'POST', '/api/campaigns/'.$frozen.'/start', null, self::TEAM_WRITERS);

        $this->assertSame(['started', 2], [$campaign['status'], $campaign['targeted']]);
    }

    public function testTheAiStopsAStartedCampaignOnceItsMemberConfirmed(): void
    {
        $started = $this->aStartedCampaign();

        $campaign = $this->assertTheToolActsAsItsEndpoint('stop-campaign', ['id' => $started], 'POST', '/api/campaigns/'.$started.'/stop', null, self::TEAM_WRITERS);

        $this->assertSame('stopped', $campaign['status']);
        $this->assertTheToolAsksForAConfirmation('stop-campaign');
    }

    public function testTheAiSetsTheDailyCapOfAStartedCampaign(): void
    {
        $started = $this->aStartedCampaign();

        $campaign = $this->assertTheToolActsAsItsEndpoint('set-campaign-daily-cap', ['id' => $started, 'dailyCap' => 2], 'PATCH', '/api/campaigns/'.$started.'/daily-cap', ['dailyCap' => 2], self::TEAM_WRITERS);

        $this->assertSame(2, $campaign['dailyCap']);
    }

    /**
     * Processed at 9:00 UTC on 15 October, read at 23:00 UTC: already the next day in Paris.
     */
    public function testTheAiReadsTheRecipientsProcessedTodayOnItsMembersClock(): void
    {
        $this->keepTheKernel();
        $started = $this->aStartedCampaign();
        $this->requestJson('POST', '/api/campaigns/'.$started.'/recipients/'.$this->ada.'/exchanges', self::SENT_TO_ADA);
        $this->theClockShows(self::LATE_THAT_DAY);

        $inParis = $this->callWithoutHandshake('get-campaign', ['id' => $started, 'timeZone' => 'Europe/Paris']);
        $inUtc = $this->callWithoutHandshake('get-campaign', ['id' => $started]);

        $this->assertSame([0, 1], [self::valueAt($inParis, 'structuredContent', 'processedToday'), self::valueAt($inUtc, 'structuredContent', 'processedToday')]);
    }

    public function testTheAiAddsAContactChosenToAFrozenCampaign(): void
    {
        $frozen = $this->aFrozenCampaign();
        $grace = $this->grace();

        $campaign = $this->assertTheToolActsAsItsEndpoint('add-campaign-recipient', ['id' => $frozen, 'contactId' => $grace], 'POST', '/api/campaigns/'.$frozen.'/recipients', ['contactId' => $grace], self::TEAM_WRITERS);

        $this->assertSame(3, $campaign['targeted']);
    }

    public function testTheAiAddsToAFrozenCampaignTheContactsCarryingTags(): void
    {
        $frozen = $this->aFrozenCampaign();
        $grace = $this->grace();
        $this->requestJson('POST', '/api/contacts/'.$grace.'/tags', ['tags' => ['#COBOL']]);

        $campaign = $this->assertTheToolActsAsItsEndpoint('add-recipients-by-tags', ['id' => $frozen, 'tags' => ['#COBOL']], 'POST', '/api/campaigns/'.$frozen.'/recipients/by-tags', ['tags' => ['#COBOL']], self::TEAM_WRITERS);

        $this->assertSame(3, $campaign['targeted']);
    }

    public function testTheAiTakesARecipientOffAFrozenCampaignOnceItsMemberConfirmed(): void
    {
        $frozen = $this->aFrozenCampaign();
        $this->assertTheToolIsRefusedAsItsEndpoint('remove-campaign-recipient', ['id' => $frozen, 'contactId' => $this->alan], 'DELETE', '/api/campaigns/'.$frozen.'/recipients/'.$this->alan, null, ['assistant-user', 'member-user', 'stranger']);
        $this->login();

        $this->assertNotTrue($this->callWithoutHandshake('remove-campaign-recipient', ['id' => $frozen, 'contactId' => $this->alan])['isError'] ?? false);

        $this->assertSame([$this->ada], array_column(array_column(self::recordsAt($this->requestJson('GET', '/api/campaigns/'.$frozen.'/recipients'), 'items'), 'contact'), 'id'));
        $this->assertTheToolAsksForAConfirmation('remove-campaign-recipient');
    }

    public function testTheAiDismissesARecipientOfAStartedCampaignOnceItsMemberConfirmed(): void
    {
        $started = $this->aStartedCampaign();
        $gone = ['reason' => 'Parti de la société'];
        $this->assertTheToolIsRefusedAsItsEndpoint('dismiss-campaign-recipient', ['id' => $started, 'contactId' => $this->alan, ...$gone], 'POST', '/api/campaigns/'.$started.'/recipients/'.$this->alan.'/dismissal', $gone, ['assistant-user', 'member-user', 'stranger']);
        $this->login();

        $this->assertNotTrue($this->callWithoutHandshake('dismiss-campaign-recipient', ['id' => $started, 'contactId' => $this->alan, ...$gone])['isError'] ?? false);

        $alan = $this->recipientOf($started, $this->alan);
        $this->assertSame(['dismissed', 'Parti de la société'], [$alan['status'] ?? null, $alan['reason'] ?? null]);
        $this->assertTheToolAsksForAConfirmation('dismiss-campaign-recipient');
    }

    public function testTheAiTakesTheNextRecipientTheirAddressAndTheMessageToSendAsIs(): void
    {
        $started = $this->aStartedCampaign();
        $first = self::recordAt($this->requestJson('GET', '/api/campaigns/'.$started.'/recipients'), 'items', 0, 'contact');

        $next = self::recordAt($this->callWithoutHandshake('get-next-recipient', ['id' => $started]), 'structuredContent');

        $this->assertSame(
            [$first['id'], self::valueAt($first, 'contactDetails', 'email'), 'email', ['subject' => 'Une mission PHP ?', 'text' => 'Bonjour '.self::stringAt($first, 'name', 'firstName')], 'asIs', 2],
            [self::valueAt($next, 'contact', 'id'), self::valueAt($next, 'contact', 'contactDetails', 'email'), $next['channel'], $next['message'], $next['messageMode'], $next['pending']],
        );
        $this->assertSame('Send the message as is, without changing a word, with your own tools: your member\'s mailbox for an e-mail, their LinkedIn for a LinkedIn message. Then log it with `log-sent-message` and the text you sent.', $next['instruction']);
        $this->assertSame(['href' => '/api/campaigns/'.$started.'/recipients/'.self::stringAt($first, 'id').'/exchanges', 'method' => 'POST'], self::valueAt($next, '_links', 'noteExchange'));
    }

    public function testTheAiMayAdaptTheMessageToTheNextRecipient(): void
    {
        $this->login();
        $id = self::stringAt($this->requestJson('POST', '/api/campaigns', [...self::RELAUNCH, 'messageMode' => 'adapted']), 'id');
        $this->requestJson('POST', '/api/campaigns/'.$id.'/freeze');
        $this->requestJson('POST', '/api/campaigns/'.$id.'/start');

        $next = self::recordAt($this->callWithoutHandshake('get-next-recipient', ['id' => $id]), 'structuredContent');

        $this->assertSame('adapted', $next['messageMode']);
        $this->assertSame('You may rewrite the message for this person, from what you know of them (their profile, your past exchanges), but always keep the message\'s idea. Send it with your own tools: your member\'s mailbox for an e-mail, their LinkedIn for a LinkedIn message. Then log it with `log-sent-message` and the text you actually sent.', $next['instruction']);
    }

    public function testTheAiIsWarnedOnceTheDailyCapIsReachedAndStillGetsTheNextRecipient(): void
    {
        $started = $this->aStartedCampaign();
        $this->requestJson('PATCH', '/api/campaigns/'.$started.'/daily-cap', ['dailyCap' => 1]);
        $this->requestJson('POST', '/api/campaigns/'.$started.'/recipients/'.$this->ada.'/exchanges', self::SENT_TO_ADA);

        $next = $this->callWithoutHandshake('get-next-recipient', ['id' => $started, 'timeZone' => 'Europe/Paris']);

        $this->assertSame(
            [$this->alan, ['sends' => 1, 'processedToday' => 1, 'reached' => true], 'Today\'s cap is reached (1). Stop, and ask your member whether to go beyond it before you send this message.'],
            [self::valueAt($next, 'structuredContent', 'contact', 'id'), self::valueAt($next, 'structuredContent', 'dailyCap'), self::valueAt($next, 'structuredContent', 'warning')],
        );
    }

    /**
     * Processed at 9:00 UTC on 15 October, asked at 23:00 UTC: already the next day in Paris, the cap no longer reached.
     */
    public function testTheDayOfTheDailyCapIsTheMembers(): void
    {
        $this->keepTheKernel();
        $started = $this->aStartedCampaign();
        $this->requestJson('PATCH', '/api/campaigns/'.$started.'/daily-cap', ['dailyCap' => 1]);
        $this->requestJson('POST', '/api/campaigns/'.$started.'/recipients/'.$this->ada.'/exchanges', self::SENT_TO_ADA);
        $this->theClockShows(self::LATE_THAT_DAY);

        $next = $this->callWithoutHandshake('get-next-recipient', ['id' => $started, 'timeZone' => 'Europe/Paris']);

        $this->assertSame(
            [['sends' => 1, 'processedToday' => 0, 'reached' => false], null],
            [self::valueAt($next, 'structuredContent', 'dailyCap'), self::valueAt($next, 'structuredContent', 'warning')],
        );
    }

    #[Group('core-rule')]
    public function testBelowTheDailyCapTheAiIsNotWarned(): void
    {
        $started = $this->aStartedCampaign();
        $this->requestJson('PATCH', '/api/campaigns/'.$started.'/daily-cap', ['dailyCap' => 2]);
        $this->requestJson('POST', '/api/campaigns/'.$started.'/recipients/'.$this->ada.'/exchanges', self::SENT_TO_ADA);

        $next = $this->callWithoutHandshake('get-next-recipient', ['id' => $started]);

        $this->assertSame(
            [['sends' => 2, 'processedToday' => 1, 'reached' => false], null],
            [self::valueAt($next, 'structuredContent', 'dailyCap'), self::valueAt($next, 'structuredContent', 'warning')],
        );
    }

    public function testTheNextRecipientIsRefusedAsTheMessageOfTheCampaign(): void
    {
        $started = $this->aStartedCampaign();

        $this->assertTheToolIsRefusedAsItsEndpoint('get-next-recipient', ['id' => $started], 'GET', '/api/campaigns/'.$started.'/messages/'.$this->ada, null, ['assistant-user', 'member-user', 'stranger']);
    }

    public function testAnAdministratorLeadsACampaignOfTheTeamAsAProspector(): void
    {
        $started = $this->aStartedCampaign();
        $this->login('admin-user');

        $next = self::recordAt($this->callWithoutHandshake('get-next-recipient', ['id' => $started]), 'structuredContent');
        $noted = $this->callWithoutHandshake('log-sent-message', ['id' => $started, 'contactId' => self::valueAt($next, 'contact', 'id'), ...self::SENT_TO_ADA]);

        $this->assertSame([2, 'noted'], [$next['pending'], self::valueAt($noted, 'structuredContent', 'status')]);
    }

    public function testASendingNotedByHandIsAnsweredAlreadyNotedToTheAi(): void
    {
        $started = $this->aStartedCampaign();
        $this->requestJson('POST', '/api/campaigns/'.$started.'/recipients/'.$this->ada.'/exchanges', self::SENT_TO_ADA);
        $this->assertResponseStatusCodeSame(201);

        $again = $this->callWithoutHandshake('log-sent-message', ['id' => $started, 'contactId' => $this->ada, ...self::SENT_TO_ADA]);

        $this->assertSame('alreadyNoted', self::valueAt($again, 'structuredContent', 'status'));
        $this->assertCount(1, self::arrayAt($this->requestJson('GET', '/api/contacts/'.$this->ada.'/exchanges'), 'items'));
    }

    public function testACampaignStoppedGivesNoNextRecipient(): void
    {
        $started = $this->aStartedCampaign();
        $this->requestJson('POST', '/api/campaigns/'.$started.'/stop');

        $refusal = $this->callWithoutHandshake('get-next-recipient', ['id' => $started]);

        $this->assertTrue($refusal['isError'] ?? false);
        $this->assertSame('campaignNotRunning', self::valueAt($this->answerOf($refusal), 'reason'));
    }

    public function testThePromptToLeadACampaignIsListedWithTheCampaignToRun(): void
    {
        $this->login();

        $prompt = $this->promptListed('run-campaign');

        $this->assertSame(['Run a campaign', 'Send each recipient of a started campaign their message with your own tools, and log it in Prospecter.'], [$prompt['title'] ?? null, $prompt['description'] ?? null]);
        $this->assertSame([['campaign', true]], self::argumentsOf($prompt));
        $this->assertSame(['the campaign\'s ID, or its name in quotes'], array_column(self::records($prompt['arguments'] ?? []), 'description'));
    }

    public function testThePromptToLeadACampaignGivesTheAiItsSteps(): void
    {
        $this->login();

        $prompt = $this->callWithHandshake('prompts/get', ['name' => 'run-campaign', 'arguments' => ['campaign' => 'Relance PHP']]);

        $text = self::stringAt($prompt, 'messages', 0, 'content', 'text');
        $this->assertSame('user', self::valueAt($prompt, 'messages', 0, 'role'));
        foreach (['"Relance PHP"', 'list-campaigns', 'get-next-recipient', 'log-sent-message', 'dismiss-campaign-recipient'] as $step) {
            $this->assertStringContainsString($step, $text);
        }
    }

    public function testThePromptToLeadACampaignAsksWhichOneWhenItsNameMatchesNoSingleStartedCampaign(): void
    {
        $this->login();

        $text = $this->promptText('run-campaign', ['campaign' => 'Relance']);

        $this->assertStringContainsString('When "Relance" is not the ID or the exact name of a single started campaign, ask me which one to run, and send nothing until I answer. Do not guess.', $text);
    }

    public function testThePromptToLeadACampaignNeverSendsTwiceToARecipient(): void
    {
        $this->login();

        $prompt = $this->callWithHandshake('prompts/get', ['name' => 'run-campaign', 'arguments' => ['campaign' => 'Relance PHP']]);

        $this->assertStringContainsString('Never send a second message to a recipient: when `get-next-recipient` gives you a recipient you already wrote to, stop and tell me who.', self::stringAt($prompt, 'messages', 0, 'content', 'text'));
    }

    public function testThePromptToLeadACampaignStopsAtTheDailyCapToAskTheMember(): void
    {
        $this->login();

        $prompt = $this->callWithHandshake('prompts/get', ['name' => 'run-campaign', 'arguments' => ['campaign' => 'Relance PHP']]);

        $this->assertStringContainsString('When `get-next-recipient` warns you that today\'s cap is reached, stop and ask me whether to go beyond it. Send nothing more until I answer.', self::stringAt($prompt, 'messages', 0, 'content', 'text'));
    }

    public function testTheAiNotesTheEmailItSentToARecipientWhoIsProcessed(): void
    {
        $started = $this->aStartedCampaign();

        $noted = $this->callWithoutHandshake('log-sent-message', ['id' => $started, 'contactId' => $this->ada, ...self::SENT_TO_ADA]);

        $exchange = self::recordAt($noted, 'structuredContent', 'exchange');
        $this->assertSame(['noted', 'email', 'outbound', 'Bonjour Ada', true], [self::valueAt($noted, 'structuredContent', 'status'), $exchange['channel'] ?? null, $exchange['direction'] ?? null, $exchange['text'] ?? null, $exchange['createdByAnAI'] ?? null]);
        $ada = $this->recipientOf($started, $this->ada);
        $this->assertSame('processed', $ada['status'] ?? null);
    }

    public function testTheSendingNotedFirstOutsideTheCampaignJoinsItAndIsAnsweredNoted(): void
    {
        $started = $this->aStartedCampaign();
        $first = $this->callWithoutHandshake('log-contact-exchange', ['id' => $this->ada, 'channel' => 'email', 'direction' => 'outbound', ...self::SENT_TO_ADA]);

        $noted = $this->callWithoutHandshake('log-sent-message', ['id' => $started, 'contactId' => $this->ada, ...self::SENT_TO_ADA]);

        $this->assertSame(['noted', self::stringAt($first, 'structuredContent', 'exchange', 'id')], [self::stringAt($noted, 'structuredContent', 'status'), self::stringAt($noted, 'structuredContent', 'exchange', 'id')]);
        $ada = $this->recipientOf($started, $this->ada);
        $this->assertSame('processed', $ada['status'] ?? null);
    }

    public function testTheSendingNotedAgainIsAnsweredTheExchangeNotedFirstWithoutError(): void
    {
        $started = $this->aStartedCampaign();
        $first = $this->callWithoutHandshake('log-sent-message', ['id' => $started, 'contactId' => $this->ada, ...self::SENT_TO_ADA]);

        $again = $this->callWithoutHandshake('log-sent-message', ['id' => $started, 'contactId' => $this->ada, ...self::SENT_TO_ADA]);

        $this->assertFalse($again['isError'] ?? false);
        $this->assertSame(['alreadyNoted', self::valueAt($first, 'structuredContent', 'exchange', 'id')], [self::valueAt($again, 'structuredContent', 'status'), self::valueAt($again, 'structuredContent', 'exchange', 'id')]);
        $this->assertCount(1, self::arrayAt($this->requestJson('GET', '/api/contacts/'.$this->ada.'/exchanges'), 'items'));
    }

    public function testNotingTheSendingIsRefusedAsItsEndpointRefusesIt(): void
    {
        $started = $this->aStartedCampaign();

        $this->assertTheToolIsRefusedAsItsEndpoint('log-sent-message', ['id' => $started, 'contactId' => $this->ada, ...self::SENT_TO_ADA], 'POST', '/api/campaigns/'.$started.'/recipients/'.$this->ada.'/exchanges', self::SENT_TO_ADA, ['assistant-user', 'member-user', 'stranger']);
    }

    public function testTheAiDiscardsADraftOnceItsMemberConfirmed(): void
    {
        $draft = $this->aDraft();
        $this->assertTheToolIsRefusedAsItsEndpoint('delete-campaign', ['id' => $draft], 'DELETE', '/api/campaigns/'.$draft, null, ['assistant-user', 'member-user', 'stranger']);
        $this->login();

        $this->assertNotTrue($this->callWithoutHandshake('delete-campaign', ['id' => $draft])['isError'] ?? false);

        $this->requestJson('GET', '/api/campaigns/'.$draft);
        $this->assertResponseStatusCodeSame(404);
        $this->assertTheToolAsksForAConfirmation('delete-campaign');
    }

    private function theClockShows(string $moment): void
    {
        $clock = $this->client->getContainer()->get('clock');
        self::assertInstanceOf(MockClock::class, $clock);
        $clock->modify($moment);
    }

    /**
     * Grace Hopper, at Initech, which carries no tag of the campaign.
     */
    private function grace(): string
    {
        $this->login();

        return $this->aContactOf(self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Initech']]), 'id'), 'Grace', 'Hopper', 'grace@initech.test');
    }

    /**
     * « Relance PHP », started for Ada Lovelace and Alan Turing.
     */
    private function aStartedCampaign(): string
    {
        $id = $this->aFrozenCampaign();
        $this->requestJson('POST', '/api/campaigns/'.$id.'/start');
        $this->assertResponseStatusCodeSame(200);

        return $id;
    }

    /**
     * « Relance PHP », its recipients Ada Lovelace and Alan Turing frozen.
     */
    private function aFrozenCampaign(): string
    {
        $id = $this->aDraft();
        $this->requestJson('POST', '/api/campaigns/'.$id.'/freeze');
        $this->assertResponseStatusCodeSame(200);

        return $id;
    }

    /**
     * « Relance PHP », prepared by the prospector.
     */
    private function aDraft(): string
    {
        $this->login();

        return self::stringAt($this->requestJson('POST', '/api/campaigns', self::RELAUNCH), 'id');
    }

    private function aContactOf(string $organization, string $firstName, string $lastName, string $email): string
    {
        return self::stringAt($this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => $firstName, 'lastName' => $lastName], 'contactDetails' => ['email' => $email], 'organizationIds' => [$organization]]), 'id');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function recipientOf(string $campaign, string $contact): ?array
    {
        return array_find(self::recordsAt($this->requestJson('GET', '/api/campaigns/'.$campaign.'/recipients'), 'items'), static fn (array $recipient): bool => $contact === self::valueAt($recipient, 'contact', 'id'));
    }
}
