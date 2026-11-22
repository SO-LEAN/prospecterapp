<?php

declare(strict_types=1);

namespace Tests\App\Campaigns;

use App\Demo\DemoData;
use App\Shared\Typed;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Base\ApiTestCase;

/**
 * `app:tags:rebuild` records again the facts the tags of the contacts derive from, as Prospecting and PersonalData give
 * them, for every account or one (ADR 0141); it writes only the table of Campaigns. The demonstration, its facts lost
 * first: SOLEAN IT prospects Babbage & Co, in Colmar (ADR 0142), whose contact Ada has an e-mail, a landline and a
 * LinkedIn profile.
 */
class RebuildTagsCommandTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testTheRebuildGivesTheContactsTheTagsOfTheirOrganizationsOfWhereTheyAreAndOfHowTheyAreReached(): void
    {
        [$babbage, $ada] = $this->babbageAndAda();
        $this->requestJson('POST', '/api/organizations/'.$babbage.'/tags', ['tags' => ['#ESN']]);
        $this->loseTheFacts();
        $this->assertSame([], $this->requestJson('GET', '/api/contacts/'.$ada.'/tags')['inherited']);

        $this->assertStringContainsString('rebuilt for 1 account(s)', $this->rebuild());

        $tags = $this->requestJson('GET', '/api/contacts/'.$ada.'/tags');
        $inherited = Typed::documents($tags['inherited']);
        $this->assertSame(['#68', '#COLMAR', '#ESN', '#GRAND_EST'], array_column($inherited, 'name'));
        $this->assertSame([[$babbage]], array_values(array_unique(array_column($inherited, 'from'), SORT_REGULAR)));
        $this->assertSame(['#EMAIL', '#LINKEDIN', '#PHONE'], $tags['automatic']);
    }

    public function testTheRebuildOfOneAccount(): void
    {
        [, $ada] = $this->babbageAndAda();
        $this->loseTheFacts();

        $this->assertStringContainsString('rebuilt for 1 account(s)', $this->rebuild(['--account' => DemoData::SOLEAN_IT]));

        $this->assertSame(['#EMAIL', '#LINKEDIN', '#PHONE'], $this->requestJson('GET', '/api/contacts/'.$ada.'/tags')['automatic']);
    }

    public function testARebuildWritesOnlyTheTableOfCampaigns(): void
    {
        $before = $this->otherTables();

        $this->rebuild();

        $this->assertEquals($before, $this->otherTables());
    }

    /**
     * @param array<string, string> $options
     */
    private function rebuild(array $options = []): string
    {
        $tester = new CommandTester(new Application(self::$kernel ?? self::bootKernel())->find('app:tags:rebuild'));
        $tester->execute($options);
        $tester->assertCommandIsSuccessful();

        return Typed::string(preg_replace('/\s+/', ' ', $tester->getDisplay()));
    }

    /**
     * @return array{string, string} the organization of the demonstration and its contact
     */
    private function babbageAndAda(): array
    {
        $ada = Typed::documents($this->requestJson('GET', '/api/contacts?q=Lovelace')['items'])[0];

        return [Typed::string(Typed::documents($ada['organizations'])[0]['id']), Typed::string($ada['id'])];
    }

    private function loseTheFacts(): void
    {
        $table = self::tableOf('campaigns');
        $facts = array_filter($table->scan('ACCOUNT#'), static fn (array $item): bool => str_starts_with(Typed::string($item['SK']), 'FACT#'));
        $table->batch([], array_values(array_map(static fn (array $item): array => [Typed::string($item['PK']), Typed::string($item['SK'])], $facts)));
    }

    /**
     * @return array<string, list<array<string, mixed>>> the items of the tables of the other contexts
     */
    private function otherTables(): array
    {
        $items = [];

        foreach (['accounts', 'personal_data', 'prospecting', 'reporting'] as $context) {
            $items[$context] = self::tableOf($context)->scan('');
        }

        return $items;
    }
}
