<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
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

    private const array RELAUNCH = ['name' => 'Relance PHP', 'channel' => 'email', 'tags' => [self::TAG], 'message' => 'Bonjour {prénom}', 'subject' => 'Une mission PHP ?'];

    private const array SENT_TO_ADA = ['text' => 'Bonjour Ada', 'messageId' => '<relance-ada@solean-it.test>'];

    /** The members a campaign of the team of the prospector is written by: another account knows nothing of it. */
    private const array TEAM_WRITERS = ['prospector-user', 'admin-user'];

    private string $ada;
    private string $alan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aMemberOfTheFixtures('admin-user', Role::User, Role::Admin);
        $this->aMemberOfTheFixtures('member-user', Role::User);
        $this->aMemberOfAnotherAccount('stranger');
        $this->login();
        $acme = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $this->requestJson('POST', '/api/organizations/'.$acme.'/tags', ['tags' => [self::TAG]]);
        $this->ada = $this->aContactOf($acme, 'Ada', 'Lovelace', 'ada@acme.test');
        $this->alan = $this->aContactOf($acme, 'Alan', 'Turing', 'alan@acme.test');
    }

    public function testTheAiPreparesACampaign(): void
    {
        $campaign = $this->assertTheToolActsAsItsEndpoint('preparer-campagne', self::RELAUNCH, 'POST', '/api/campaigns', self::RELAUNCH, [...self::PERSONAL_DATA_MANAGERS, 'stranger']);

        $this->assertSame(['Relance PHP', 'email', 'draft'], [$campaign['name'], $campaign['channel'], $campaign['status']]);
    }

    public function testTheAiRevisesADraft(): void
    {
        $draft = $this->aDraft();
        $revised = [...self::RELAUNCH, 'message' => 'Bonjour {prénom}, une mission PHP vous intéresse ?'];

        $campaign = $this->assertTheToolActsAsItsEndpoint('reviser-campagne', ['id' => $draft, ...$revised], 'PATCH', '/api/campaigns/'.$draft, $revised, self::TEAM_WRITERS);

        $this->assertSame('Bonjour {prénom}, une mission PHP vous intéresse ?', $campaign['message']);
    }

    public function testTheAiFreezesTheRecipientsOfADraft(): void
    {
        $draft = $this->aDraft();

        $campaign = $this->assertTheToolActsAsItsEndpoint('figer-campagne', ['id' => $draft], 'POST', '/api/campaigns/'.$draft.'/freezing', null, self::TEAM_WRITERS);

        $this->assertSame('frozen', $campaign['status']);
    }

    public function testTheAiStartsAFrozenCampaignItsRecipientsKept(): void
    {
        $frozen = $this->aFrozenCampaign();

        $campaign = $this->assertTheToolActsAsItsEndpoint('demarrer-campagne', ['id' => $frozen], 'POST', '/api/campaigns/'.$frozen.'/starting', null, self::TEAM_WRITERS);

        $this->assertSame(['started', 2], [$campaign['status'], $campaign['targeted']]);
    }

    public function testTheAiStopsAStartedCampaignOnceItsMemberConfirmed(): void
    {
        $started = $this->aStartedCampaign();

        $campaign = $this->assertTheToolActsAsItsEndpoint('arreter-campagne', ['id' => $started], 'POST', '/api/campaigns/'.$started.'/stopping', null, self::TEAM_WRITERS);

        $this->assertSame('stopped', $campaign['status']);
        $this->assertTheToolAsksForAConfirmation('arreter-campagne');
    }

    public function testTheAiAddsAContactChosenToAFrozenCampaign(): void
    {
        $frozen = $this->aFrozenCampaign();
        $grace = $this->grace();

        $campaign = $this->assertTheToolActsAsItsEndpoint('ajouter-destinataire-campagne', ['id' => $frozen, 'contactId' => $grace], 'POST', '/api/campaigns/'.$frozen.'/recipients', ['contactId' => $grace], self::TEAM_WRITERS);

        $this->assertSame(3, $campaign['targeted']);
    }

    public function testTheAiAddsToAFrozenCampaignTheContactsCarryingTags(): void
    {
        $frozen = $this->aFrozenCampaign();
        $grace = $this->grace();
        $this->requestJson('POST', '/api/contacts/'.$grace.'/tags', ['tags' => ['#COBOL']]);

        $campaign = $this->assertTheToolActsAsItsEndpoint('ajouter-destinataires-par-tags', ['id' => $frozen, 'tags' => ['#COBOL']], 'POST', '/api/campaigns/'.$frozen.'/recipients/by-tags', ['tags' => ['#COBOL']], self::TEAM_WRITERS);

        $this->assertSame(3, $campaign['targeted']);
    }

    public function testTheAiTakesARecipientOffAFrozenCampaignOnceItsMemberConfirmed(): void
    {
        $frozen = $this->aFrozenCampaign();
        $this->assertTheToolIsRefusedAsItsEndpoint('retirer-destinataire-campagne', ['id' => $frozen, 'contactId' => $this->alan], 'DELETE', '/api/campaigns/'.$frozen.'/recipients/'.$this->alan, null, ['assistant-user', 'member-user', 'stranger']);
        $this->login();

        $this->assertNotTrue($this->callWithoutHandshake('retirer-destinataire-campagne', ['id' => $frozen, 'contactId' => $this->alan])['isError'] ?? false);

        $this->assertSame([$this->ada], array_column(array_column($this->requestJson('GET', '/api/campaigns/'.$frozen.'/recipients')['items'], 'contact'), 'id'));
        $this->assertTheToolAsksForAConfirmation('retirer-destinataire-campagne');
    }

    public function testTheAiDismissesARecipientOfAStartedCampaignOnceItsMemberConfirmed(): void
    {
        $started = $this->aStartedCampaign();
        $gone = ['reason' => 'Parti de la société'];
        $this->assertTheToolIsRefusedAsItsEndpoint('ecarter-destinataire-campagne', ['id' => $started, 'contactId' => $this->alan, ...$gone], 'POST', '/api/campaigns/'.$started.'/recipients/'.$this->alan.'/dismissal', $gone, ['assistant-user', 'member-user', 'stranger']);
        $this->login();

        $this->assertNotTrue($this->callWithoutHandshake('ecarter-destinataire-campagne', ['id' => $started, 'contactId' => $this->alan, ...$gone])['isError'] ?? false);

        $alan = array_find($this->requestJson('GET', '/api/campaigns/'.$started.'/recipients')['items'], fn (array $recipient): bool => $this->alan === $recipient['contact']['id']);
        $this->assertSame(['dismissed', 'Parti de la société'], [$alan['status'] ?? null, $alan['reason'] ?? null]);
        $this->assertTheToolAsksForAConfirmation('ecarter-destinataire-campagne');
    }

    public function testTheAiTakesTheNextRecipientTheirAddressAndTheMessageToSendAsIs(): void
    {
        $started = $this->aStartedCampaign();
        $first = $this->requestJson('GET', '/api/campaigns/'.$started.'/recipients')['items'][0]['contact'];

        $next = $this->callWithoutHandshake('destinataire-suivant', ['id' => $started])['structuredContent'];

        $this->assertSame(
            [$first['id'], $first['contactDetails']['email'], 'email', ['subject' => 'Une mission PHP ?', 'text' => 'Bonjour '.$first['name']['firstName']], 'asIs', 2],
            [$next['contact']['id'], $next['contact']['contactDetails']['email'], $next['channel'], $next['message'], $next['messageMode'], $next['pending']],
        );
        $this->assertSame('Send the message as it is, without changing a word, by your own means (the mailbox of your member for an e-mail, their LinkedIn for a LinkedIn message), then note it by noter-l-envoi with the text sent.', $next['instruction']);
        $this->assertSame(['href' => '/api/campaigns/'.$started.'/recipients/'.$first['id'].'/exchanges', 'method' => 'POST'], $next['_links']['noteExchange']);
    }

    public function testTheAiMayAdaptTheMessageToTheNextRecipient(): void
    {
        $this->login();
        $id = $this->requestJson('POST', '/api/campaigns', [...self::RELAUNCH, 'messageMode' => 'adapted'])['id'];
        $this->requestJson('POST', '/api/campaigns/'.$id.'/freezing');
        $this->requestJson('POST', '/api/campaigns/'.$id.'/starting');

        $next = $this->callWithoutHandshake('destinataire-suivant', ['id' => $id])['structuredContent'];

        $this->assertSame('adapted', $next['messageMode']);
        $this->assertSame('You may rewrite the message for this person, from what you read of them (their profile, their past exchanges), always keeping the idea of the message: send it by your own means (the mailbox of your member for an e-mail, their LinkedIn for a LinkedIn message), then note it by noter-l-envoi with the text really sent.', $next['instruction']);
    }

    public function testTheNextRecipientIsRefusedAsTheMessageOfTheCampaign(): void
    {
        $started = $this->aStartedCampaign();

        $this->assertTheToolIsRefusedAsItsEndpoint('destinataire-suivant', ['id' => $started], 'GET', '/api/campaigns/'.$started.'/messages/'.$this->ada, null, ['assistant-user', 'member-user', 'stranger']);
    }

    public function testAnAdministratorLeadsACampaignOfTheTeamAsAProspector(): void
    {
        $started = $this->aStartedCampaign();
        $this->login('admin-user');

        $next = $this->callWithoutHandshake('destinataire-suivant', ['id' => $started])['structuredContent'];
        $noted = $this->callWithoutHandshake('noter-l-envoi', ['id' => $started, 'contactId' => $next['contact']['id'], ...self::SENT_TO_ADA]);

        $this->assertSame([2, 'noted'], [$next['pending'], $noted['structuredContent']['status']]);
    }

    public function testASendingNotedByHandIsAnsweredAlreadyNotedToTheAi(): void
    {
        $started = $this->aStartedCampaign();
        $this->requestJson('POST', '/api/campaigns/'.$started.'/recipients/'.$this->ada.'/exchanges', self::SENT_TO_ADA);
        $this->assertResponseStatusCodeSame(201);

        $again = $this->callWithoutHandshake('noter-l-envoi', ['id' => $started, 'contactId' => $this->ada, ...self::SENT_TO_ADA]);

        $this->assertSame('alreadyNoted', $again['structuredContent']['status']);
        $this->assertCount(1, $this->requestJson('GET', '/api/contacts/'.$this->ada.'/exchanges')['items']);
    }

    public function testACampaignStoppedGivesNoNextRecipient(): void
    {
        $started = $this->aStartedCampaign();
        $this->requestJson('POST', '/api/campaigns/'.$started.'/stopping');

        $refusal = $this->callWithoutHandshake('destinataire-suivant', ['id' => $started]);

        $this->assertTrue($refusal['isError'] ?? false);
        $this->assertSame('campaignNotRunning', json_decode($refusal['content'][0]['text'], true, flags: JSON_THROW_ON_ERROR)['reason']);
    }

    public function testThePromptToLeadACampaignGivesTheAiItsSteps(): void
    {
        $this->login();

        $prompt = $this->callWithHandshake('prompts/get', ['name' => 'mener-une-campagne', 'arguments' => ['campagne' => 'Relance PHP']]);

        $text = $prompt['messages'][0]['content']['text'];
        $this->assertSame('user', $prompt['messages'][0]['role']);
        foreach (['« Relance PHP »', 'lister-campagnes', 'destinataire-suivant', 'noter-l-envoi', 'ecarter-destinataire-campagne'] as $step) {
            $this->assertStringContainsString($step, $text);
        }
    }

    public function testThePromptToLeadACampaignNeverSendsTwiceToARecipient(): void
    {
        $this->login();

        $prompt = $this->callWithHandshake('prompts/get', ['name' => 'mener-une-campagne', 'arguments' => ['campagne' => 'Relance PHP']]);

        $this->assertStringContainsString('N\'envoie jamais un second message à un destinataire : quand `destinataire-suivant` te redonne un destinataire à qui tu as écrit, arrête-toi et dis-moi qui.', $prompt['messages'][0]['content']['text']);
    }

    public function testTheAiNotesTheEmailItSentToARecipientWhoIsProcessed(): void
    {
        $started = $this->aStartedCampaign();

        $noted = $this->callWithoutHandshake('noter-l-envoi', ['id' => $started, 'contactId' => $this->ada, ...self::SENT_TO_ADA]);

        $this->assertSame(['noted', 'email', 'outbound', 'Bonjour Ada'], [$noted['structuredContent']['status'], $noted['structuredContent']['exchange']['channel'] ?? null, $noted['structuredContent']['exchange']['direction'] ?? null, $noted['structuredContent']['exchange']['text'] ?? null]);
        $ada = array_find($this->requestJson('GET', '/api/campaigns/'.$started.'/recipients')['items'], fn (array $recipient): bool => $this->ada === $recipient['contact']['id']);
        $this->assertSame('processed', $ada['status'] ?? null);
    }

    public function testTheSendingNotedAgainIsAnsweredTheExchangeNotedFirstWithoutError(): void
    {
        $started = $this->aStartedCampaign();
        $first = $this->callWithoutHandshake('noter-l-envoi', ['id' => $started, 'contactId' => $this->ada, ...self::SENT_TO_ADA]);

        $again = $this->callWithoutHandshake('noter-l-envoi', ['id' => $started, 'contactId' => $this->ada, ...self::SENT_TO_ADA]);

        $this->assertFalse($again['isError'] ?? false);
        $this->assertSame(['alreadyNoted', $first['structuredContent']['exchange']['id']], [$again['structuredContent']['status'], $again['structuredContent']['exchange']['id'] ?? null]);
        $this->assertCount(1, $this->requestJson('GET', '/api/contacts/'.$this->ada.'/exchanges')['items']);
    }

    public function testNotingTheSendingIsRefusedAsItsEndpointRefusesIt(): void
    {
        $started = $this->aStartedCampaign();

        $this->assertTheToolIsRefusedAsItsEndpoint('noter-l-envoi', ['id' => $started, 'contactId' => $this->ada, ...self::SENT_TO_ADA], 'POST', '/api/campaigns/'.$started.'/recipients/'.$this->ada.'/exchanges', self::SENT_TO_ADA, ['assistant-user', 'member-user', 'stranger']);
    }

    public function testTheAiDiscardsADraftOnceItsMemberConfirmed(): void
    {
        $draft = $this->aDraft();
        $this->assertTheToolIsRefusedAsItsEndpoint('abandonner-campagne', ['id' => $draft], 'DELETE', '/api/campaigns/'.$draft, null, ['assistant-user', 'member-user', 'stranger']);
        $this->login();

        $this->assertNotTrue($this->callWithoutHandshake('abandonner-campagne', ['id' => $draft])['isError'] ?? false);

        $this->requestJson('GET', '/api/campaigns/'.$draft);
        $this->assertResponseStatusCodeSame(404);
        $this->assertTheToolAsksForAConfirmation('abandonner-campagne');
    }

    /**
     * Grace Hopper, at Initech, which carries no tag of the campaign.
     */
    private function grace(): string
    {
        $this->login();

        return $this->aContactOf($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Initech']])['id'], 'Grace', 'Hopper', 'grace@initech.test');
    }

    /**
     * « Relance PHP », started for Ada Lovelace and Alan Turing.
     */
    private function aStartedCampaign(): string
    {
        $id = $this->aFrozenCampaign();
        $this->requestJson('POST', '/api/campaigns/'.$id.'/starting');
        $this->assertResponseStatusCodeSame(200);

        return $id;
    }

    /**
     * « Relance PHP », its recipients Ada Lovelace and Alan Turing frozen.
     */
    private function aFrozenCampaign(): string
    {
        $id = $this->aDraft();
        $this->requestJson('POST', '/api/campaigns/'.$id.'/freezing');
        $this->assertResponseStatusCodeSame(200);

        return $id;
    }

    /**
     * « Relance PHP », prepared by the prospector.
     */
    private function aDraft(): string
    {
        $this->login();

        return $this->requestJson('POST', '/api/campaigns', self::RELAUNCH)['id'];
    }

    private function aContactOf(string $organization, string $firstName, string $lastName, string $email): string
    {
        return $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => $firstName, 'lastName' => $lastName], 'contactDetails' => ['email' => $email], 'organizationIds' => [$organization]])['id'];
    }
}
