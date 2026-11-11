<?php

namespace Tests\App\Accounts;

use App\Accounts\Persistence\AccountsKeys;
use App\Accounts\Persistence\DynamoAccountRepository;
use App\Shared\Persistence\Table;
use Prospecter\Core\Accounts\Application\Port\AccountListRetriever;
use Prospecter\Core\Accounts\Domain\Model\Account;
use Prospecter\Core\Accounts\Domain\Repository\AccountRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Tests\App\Base\ApiTestCase;

/**
 * The identities of the accounts, read in `GSI1` of the items `DynamoAccountRepository` writes, newest first by
 * slice; an account written before they were listed is listed once its keys are written again.
 */
class DynamoAccountListRetrieverTest extends ApiTestCase
{
    public function testTheAccountsComeBySliceNewestFirst(): void
    {
        $accounts = array_map($this->opened(...), ['ACME', 'Globex', 'Initech']);
        $afterThem = self::newIdentity();

        $first = $this->retriever()->sliceOf($afterThem, 2);
        $next = $this->retriever()->sliceOf($first->next, 1);

        $this->assertSame([$accounts[2]->id()->value, $accounts[1]->id()->value], $first->content);
        $this->assertSame([$accounts[0]->id()->value], $next->content);
    }

    public function testTheAccountsWrittenBeforeTheirListingAreListedOnceTheirKeysAreWrittenAgain(): void
    {
        $account = $this->opened('ACME');
        $afterIt = self::newIdentity();
        $table = self::getContainer()->get('app.table.accounts');
        $this->assertInstanceOf(Table::class, $table);
        $item = $table->item(AccountsKeys::account($account->id()->value), AccountsKeys::ACCOUNT);
        unset($item['GSI1PK'], $item['GSI1SK']);
        $table->batch([$item]);
        $this->assertNotContains($account->id()->value, $this->retriever()->sliceOf($afterIt, 1)->content);

        self::getContainer()->get(DynamoAccountRepository::class)->writeTheKeysAgain();

        $this->assertSame([$account->id()->value], $this->retriever()->sliceOf($afterIt, 1)->content);
    }

    private function opened(string $name): Account
    {
        $account = Account::open(new AccountId(self::newIdentity()), new LegalIdentity($name), new \DateTimeImmutable());
        self::getContainer()->get(AccountRepository::class)->save($account);

        return $account;
    }

    private function retriever(): AccountListRetriever
    {
        return self::getContainer()->get(AccountListRetriever::class);
    }
}
