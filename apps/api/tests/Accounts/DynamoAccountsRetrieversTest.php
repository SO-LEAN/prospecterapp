<?php

namespace Tests\App\Accounts;

use App\Shared\Persistence\Hydrator;
use PHPUnit\Framework\Attributes\DataProvider;
use Prospecter\Core\Accounts\Application\Port\MemberRetriever;
use Prospecter\Core\Accounts\Application\Port\MyAccountRetriever;
use Prospecter\Core\Accounts\Domain\Exception\UnknownMember;
use Prospecter\Core\Accounts\Domain\Model\Account;
use Prospecter\Core\Accounts\Domain\Model\AccountIntent;
use Prospecter\Core\Accounts\Domain\Model\Member;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Accounts\Domain\Repository\AccountRepository;
use Prospecter\Core\Accounts\Domain\Repository\MemberRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Address;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * My account and a member as the other contexts know them, read from the items the repositories write,
 * without restoring them, with what the policies of Accounts answer (ADR 0088).
 */
class DynamoAccountsRetrieversTest extends ApiTestCase
{
    public function testMyAccountTellsWhoIAmAndWhatIMayChangeOfMyOrganization(): void
    {
        $account = $this->anAccount();
        $me = $this->aMemberOf($account, Role::User, Role::Prospector);

        $myAccount = self::getContainer()->get(MyAccountRetriever::class)->of($me->id());

        $this->assertSame([$me->id()->value, $account->id()->value, Hydrator::stateOf($me)['personId']->value], [$myAccount->memberId, $myAccount->accountId, $myAccount->personId]);
        $this->assertSame(['ROLE_USER', 'ROLE_PROSPECTOR'], $myAccount->roles);
        $this->assertSame(['SOLEAN IT', 'Strasbourg'], [$myAccount->organizationCorporateName, $myAccount->organizationCity]);
        $this->assertSame(array_map(static fn (AccountIntent $intent): string => $intent->name, AccountIntent::cases()), $myAccount->accountIntents);
    }

    public function testAMemberNeverRegisteredHasNoAccount(): void
    {
        $this->expectException(UnknownMember::class);

        self::getContainer()->get(MyAccountRetriever::class)->of(new MemberId(self::newIdentity()));
    }

    /**
     * @param list<Role> $roles
     */
    #[DataProvider('rights')]
    public function testAMemberReadsThePersonalDataWithTheRightOrAsAnAdministrator(array $roles, bool $readsPersonalData): void
    {
        $account = $this->anAccount();
        $member = $this->aMemberOf($account, ...$roles);

        $published = self::getContainer()->get(MemberRetriever::class)->get($member->id());

        $this->assertSame([$member->id()->value, $account->id()->value, Hydrator::stateOf($member)['personId']->value, $readsPersonalData], [$published->id, $published->accountId, $published->personId, $published->readsPersonalData]);
    }

    /**
     * @return iterable<string, array{list<Role>, bool}>
     */
    public static function rights(): iterable
    {
        yield 'a prospector' => [[Role::User, Role::Prospector], false];
        yield 'with the right to manage personal data' => [[Role::User, Role::Prospector, Role::PersonalData], true];
        yield 'an administrator' => [[Role::User, Role::Admin], true];
    }

    private function anAccount(): Account
    {
        $account = Account::open(new AccountId(self::newIdentity()), new LegalIdentity('SOLEAN IT'), new \DateTimeImmutable());
        $account->relocateTo(new Address(city: 'Strasbourg'), null, Member::register(new MemberId(self::newIdentity()), $account->id(), new PersonId(self::newIdentity()), Role::Admin), new \DateTimeImmutable());
        self::getContainer()->get(AccountRepository::class)->save($account);

        return $account;
    }

    private function aMemberOf(Account $account, Role ...$roles): Member
    {
        $member = Member::register(new MemberId(self::newIdentity()), $account->id(), new PersonId(self::newIdentity()), ...$roles);
        self::getContainer()->get(MemberRepository::class)->save($member);

        return $member;
    }
}
