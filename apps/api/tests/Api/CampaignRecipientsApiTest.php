<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ApiTestCase;

/**
 * The recipients of a frozen campaign: the contacts its search found once, then changed by hand only, with the right
 * to manage personal data only (ADR 0081, 0144).
 */
#[Group('api')]
class CampaignRecipientsApiTest extends ApiTestCase
{
    private const string TAG = '#CAMPAIGN_152';
    private const string OTHER_TAG = '#CAMPAIGN_152_GLOBEX';

    private string $ada;
    private string $alan;
    private string $grace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
        $acme = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $this->requestJson('POST', '/api/organizations/'.$acme.'/tags', ['tags' => [self::TAG]]);
        $globex = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Globex']])['id'];
        $this->requestJson('POST', '/api/organizations/'.$globex.'/tags', ['tags' => [self::OTHER_TAG]]);
        $this->ada = $this->aContact('Ada', 'Lovelace', $acme, 'ada@acme.test');
        $this->alan = $this->aContact('Alan', 'Turing', $globex, 'alan@globex.test');
        $this->grace = $this->aContact('Grace', 'Hopper', $globex);
    }

    public function testAFrozenCampaignKeepsTheContactsItsSearchFoundToHandle(): void
    {
        $draft = $this->requestJson('POST', '/api/campaigns', $this->aCampaign());
        $this->assertSame(['href' => '/api/campaigns/'.$draft['id'].'/freeze', 'method' => 'POST'], $draft['_links']['freeze']);

        $frozen = $this->requestJson('POST', '/api/campaigns/'.$draft['id'].'/freeze');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['frozen', 1], [$frozen['status'], $frozen['targeted']]);
        $this->assertSame(['self', 'revise', 'discard', 'addRecipient', 'addRecipientsByTags', 'start', 'recipients'], array_keys($frozen['_links']));
        $recipients = $this->requestJson('GET', $frozen['_links']['recipients']['href']);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([[$this->ada, 'Ada', 'pending']], array_map(static fn (array $recipient): array => [$recipient['contact']['id'], $recipient['contact']['name']['firstName'], $recipient['status']], $recipients['items']));
        $this->assertSame(['href' => '/api/campaigns/'.$draft['id'].'/recipients/'.$this->ada, 'method' => 'DELETE'], $recipients['items'][0]['_links']['remove']);
    }

    public function testFrozenItNoLongerFollowsTheTags(): void
    {
        $id = $this->aFrozenCampaign();
        $this->requestJson('POST', '/api/contacts/'.$this->alan.'/tags', ['tags' => [self::TAG]]);

        $this->assertSame([$this->ada], $this->recipientsOf($id));
    }

    public function testFrozenItsSearchNoLongerChangesButItsNameAndItsMessageDo(): void
    {
        $id = $this->aFrozenCampaign();

        $problem = $this->requestJson('PATCH', '/api/campaigns/'.$id, [...$this->aCampaign(), 'tags' => ['#AUTRE']]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['tags'], 'frozenSearch'], [$this->violatedFields($problem), $problem['reason']]);

        $revised = $this->requestJson('PATCH', '/api/campaigns/'.$id, [...$this->aCampaign(), 'name' => 'Relance de l’hiver', 'message' => 'Bonsoir {firstName}']);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['Relance de l’hiver', 'Bonsoir {firstName}', 'frozen'], [$revised['name'], $revised['message'], $revised['status']]);
    }

    public function testRecipientsAreAddedByTagsOrChosenEachOnce(): void
    {
        $id = $this->aFrozenCampaign();

        $campaign = $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients/by-tags', ['tags' => [self::OTHER_TAG]]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(2, $campaign['targeted']);
        $this->assertEqualsCanonicalizing([$this->ada, $this->alan], $this->recipientsOf($id));

        $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients', ['contactId' => $this->alan]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(2, $this->recipientsOf($id));
    }

    public function testAContactWithoutTheMeansOfTheChannelIsNotAdded(): void
    {
        $id = $this->aFrozenCampaign();

        $problem = $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients', ['contactId' => $this->grace]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['unreachableContact', ['contact' => 'Grace Hopper', 'channel' => 'email']], [$problem['reason'], $problem['violations'][0]['context']]);
    }

    public function testAPersonWhoIsNoContactOfTheAccountIsNotAdded(): void
    {
        $problem = $this->requestJson('POST', '/api/campaigns/'.$this->aFrozenCampaign().'/recipients', ['contactId' => self::newIdentity()]);

        $this->assertResponseStatusCodeSame(404);
        $this->assertSame('unknownContact', $problem['reason']);
    }

    public function testARecipientIsTakenOffButNeverTheLast(): void
    {
        $id = $this->aFrozenCampaign();
        $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients', ['contactId' => $this->alan]);

        $this->requestJson('DELETE', '/api/campaigns/'.$id.'/recipients/'.$this->alan);

        $this->assertResponseStatusCodeSame(204);
        $this->assertSame([$this->ada], $this->recipientsOf($id));
        $this->assertSame('campaignWithoutRecipient', $this->requestJson('DELETE', '/api/campaigns/'.$id.'/recipients/'.$this->ada)['reason']);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('unknownRecipient', $this->requestJson('DELETE', '/api/campaigns/'.$id.'/recipients/'.$this->grace)['reason']);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAContactForgottenLeavesTheFrozenCampaigns(): void
    {
        $id = $this->aFrozenCampaign();
        $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients', ['contactId' => $this->alan]);

        $this->requestJson('DELETE', '/api/contacts/'.$this->alan);

        $this->assertSame([$this->ada], $this->recipientsOf($id));
    }

    public function testACampaignTargetingNobodyIsNotFrozen(): void
    {
        $id = $this->requestJson('POST', '/api/campaigns', [...$this->aCampaign(), 'tags' => ['#PERSONNE']])['id'];

        $problem = $this->requestJson('POST', '/api/campaigns/'.$id.'/freeze');

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('campaignWithoutRecipient', $problem['reason']);
    }

    public function testTheRecipientsOfADraftDoNotChangeByHand(): void
    {
        $draft = $this->requestJson('POST', '/api/campaigns', $this->aCampaign());
        $this->assertArrayNotHasKey('addRecipient', $draft['_links']);

        $this->requestJson('POST', '/api/campaigns/'.$draft['id'].'/recipients', ['contactId' => $this->alan]);

        $this->assertResponseStatusCodeSame(409);
    }

    public function testAnAdministratorChangesTheRecipients(): void
    {
        $this->login($this->aMemberOfTheFixtures('administrator', Role::Prospector, Role::Admin));
        $ada = $this->aContact('Ada', 'Byron', $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Initech']])['id'], 'ada@initech.test');
        $this->requestJson('POST', '/api/contacts/'.$ada.'/tags', ['tags' => [self::TAG]]);
        $id = $this->requestJson('POST', '/api/campaigns', $this->aCampaign())['id'];

        foreach ($this->gesturesOn($id, $ada) as [$method, $path, $payload, $status]) {
            $this->requestJson($method, $path, $payload);
            $this->assertResponseStatusCodeSame($status, $method.' '.$path);
        }
    }

    public function testWithoutTheRightToManagePersonalDataTheRecipientsAreNeitherReadNorChanged(): void
    {
        $id = $this->aFrozenCampaign();
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        foreach ($this->gesturesOn($id, $this->ada) as [$method, $path, $payload]) {
            $this->requestJson($method, $path, $payload);
            $this->assertResponseStatusCodeSame(403, $method.' '.$path);
        }
    }

    /**
     * Freezing, reading, adding and removing, in the order a member does it.
     *
     * @return list<array{string, string, array<string, mixed>|null, int}>
     */
    private function gesturesOn(string $id, string $contact): array
    {
        return [
            ['POST', '/api/campaigns/'.$id.'/freeze', null, 200],
            ['GET', '/api/campaigns/'.$id.'/recipients', null, 200],
            ['POST', '/api/campaigns/'.$id.'/recipients/by-tags', ['tags' => [self::OTHER_TAG]], 200],
            ['POST', '/api/campaigns/'.$id.'/recipients', ['contactId' => $contact], 200],
            ['DELETE', '/api/campaigns/'.$id.'/recipients/'.$this->ada, null, 204],
        ];
    }

    private function aFrozenCampaign(): string
    {
        $id = $this->requestJson('POST', '/api/campaigns', $this->aCampaign())['id'];
        $this->requestJson('POST', '/api/campaigns/'.$id.'/freeze');
        $this->assertResponseStatusCodeSame(200);

        return $id;
    }

    /**
     * @return list<string>
     */
    private function recipientsOf(string $id): array
    {
        return array_map(static fn (array $recipient): string => $recipient['contact']['id'], $this->requestJson('GET', '/api/campaigns/'.$id.'/recipients')['items']);
    }

    private function aContact(string $firstName, string $lastName, string $organization, ?string $email = null): string
    {
        return $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => $firstName, 'lastName' => $lastName], 'contactDetails' => ['email' => $email], 'organizationIds' => [$organization]])['id'];
    }

    /**
     * @return array<string, mixed>
     */
    private function aCampaign(): array
    {
        return ['name' => 'Relance ESN', 'channel' => 'email', 'tags' => [self::TAG], 'message' => 'Bonjour {firstName}', 'subject' => null];
    }
}
