<?php

declare(strict_types=1);

namespace Tests\App\Shared;

use App\Demo\DemoCredentials;
use App\Demo\DemoData;
use App\Shared\Persistence\Tables;
use App\Shared\Typed;
use App\SignIn\UserPool;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\DynamoDb\DynamoDbClient;
use Prospecter\Core\Reporting\Application\ConsultFigures\FiguresResponse;
use Prospecter\Core\Reporting\Application\Port\FiguresRetriever;
use Prospecter\Core\Reporting\Application\Port\ProjectionVersions;
use Prospecter\Core\Reporting\Domain\Model\Month;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\ConflictingFactRepository;

/**
 * `app:demo:restore` gives a running stack the demonstration back before its journeys: what they added is removed,
 * what they changed is made again, the figures are counted, and the tables and the user pool are kept.
 */
class RestoreDemonstrationCommandTest extends ApiTestCase
{
    public function testTheRestoreRemovesWhatWasAddedSinceTheDemonstration(): void
    {
        $keys = $this->keysOf('accounts');
        $this->aMemberOfAnotherAccount('another-user');

        $this->restore();

        $this->assertSame($keys, $this->keysOf('accounts'));
    }

    public function testTheRestoreGivesTheStackItsContactToAttach(): void
    {
        $this->restore();

        $this->login();
        $this->assertSame([['firstName' => 'Grace', 'lastName' => 'Hopper']], array_column(Typed::documents($this->requestJson('GET', '/api/contacts?q=Hopper')['items']), 'name'));
    }

    public function testTheRestoreRemovesTheMembersAddedFromTheUserPool(): void
    {
        $this->aMemberOfAnotherAccount('another-user');

        $this->restore();

        $this->assertSame(array_keys(DemoCredentials::LOGIN_EMAILS), $this->usersOfThePool());
    }

    public function testTheRestoreKeepsTheTablesAndTheUserPool(): void
    {
        $kept = [self::service(UserPool::class)->id(), ...$this->streams()];

        $this->restore();

        $this->assertSame($kept, [self::service(UserPool::class)->id(), ...$this->streams()]);
    }

    public function testTheRestoreCountsTheFiguresOfTheDemonstration(): void
    {
        $this->restore();

        $this->assertEquals(new FiguresResponse(activeOrganizations: 1, locatedOrganizations: 1, contacts: 2, exchanges: 2, exchangesThisMonth: 2), $this->figuresOfTheDemonstration());
    }

    public function testTheRestoreCountsTheFiguresWithoutWaitingForTheWorker(): void
    {
        $before = $this->clock()->now();

        $this->restore();

        $this->assertEquals($before, $this->clock()->now());
    }

    public function testACountCancelledByAConflictStartsAgainAfterWaitingForTheWorker(): void
    {
        self::service(ConflictingFactRepository::class)->conflict(times: 1);
        $before = $this->clock()->now();

        $this->restore();

        $this->assertEquals($before->modify('+5 minutes'), $this->clock()->now());
        $this->assertEquals(new FiguresResponse(activeOrganizations: 1, locatedOrganizations: 1, contacts: 2, exchanges: 2, exchangesThisMonth: 2), $this->figuresOfTheDemonstration());
    }

    private function clock(): MockClock
    {
        return self::service(MockClock::class, 'clock');
    }

    private function figuresOfTheDemonstration(): FiguresResponse
    {
        $versions = self::service(ProjectionVersions::class);
        $this->assertNotNull($versions->active(), 'The figures are counted');

        return self::service(FiguresRetriever::class)->of($versions->active(), new AccountId(DemoData::SOLEAN_IT), Month::of(new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)));
    }

    /**
     * @return list<string> the stream of each table, which a recreated table changes
     */
    private function streams(): array
    {
        return array_map(static fn (string $name): string => Typed::string(Typed::document(self::service(DynamoDbClient::class)->describeTable(['TableName' => $name])['Table'])['LatestStreamArn']), self::service(Tables::class)->names());
    }

    /**
     * @return list<string> the users of the user pool, sorted
     */
    private function usersOfThePool(): array
    {
        $users = array_map(static fn (array $user): string => Typed::string($user['Username']), Typed::documents(self::service(CognitoIdentityProviderClient::class)->listUsers(['UserPoolId' => self::service(UserPool::class)->id()])['Users']));
        sort($users);

        return $users;
    }

    /**
     * @return list<string> the keys of the items of a context's table, but those of its outbox
     */
    private function keysOf(string $context): array
    {
        $keys = array_map(static fn (array $item): string => Typed::string($item['PK']).'|'.Typed::string($item['SK']), self::tableOf($context)->scan('ACCOUNT#'));
        sort($keys);

        return $keys;
    }

    private function restore(): void
    {
        $tester = new CommandTester(new Application(self::$kernel ?? self::bootKernel())->find('app:demo:restore'));
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();
    }
}
