<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Accounts\Domain\Model\Account;
use Prospecter\Core\Accounts\Domain\Model\AccountIntent;
use Prospecter\Core\Accounts\Domain\Model\Member;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Accounts\Domain\Policy\AccountPossibilities;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Address;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Like the organizations: the account refuses an intention exactly when the policy does not offer it (ADR 0088).
 */
class AccountIntentsTest extends TestCase
{
    private const string MINE = '0190a1c0-0000-7000-8000-000000000001';
    private const string THEIRS = '0190a1c0-0000-7000-8000-000000000002';

    #[DataProvider('situations')]
    public function testAnIntentionIsRefusedExactlyWhenItIsNotOffered(AccountIntent $intent, string $memberAccount): void
    {
        $member = Member::register(new MemberId('0190a1c0-0000-7000-8000-0000000000aa'), new AccountId($memberAccount), new PersonId('0190a1c0-0000-7000-8000-0000000000bb'), Role::User);
        $account = Account::open(new AccountId(self::MINE), new LegalIdentity('SOLEAN IT'), new \DateTimeImmutable());
        $offered = in_array($intent, AccountPossibilities::given(self::MINE === $memberAccount), true);

        $accepted = $this->isAccepted($intent, $account, $member);

        $this->assertSame($offered, $accepted);
        $this->assertSame($accepted, [] !== $account->releaseEvents(), 'an accepted intention records what happened, a refused one nothing');
    }

    /**
     * @return iterable<string, array{AccountIntent, string}>
     */
    public static function situations(): iterable
    {
        foreach (AccountIntent::cases() as $intent) {
            foreach (['member' => self::MINE, 'outsider' => self::THEIRS] as $who => $account) {
                yield sprintf('%s by a %s', $intent->name, $who) => [$intent, $account];
            }
        }
    }

    private function isAccepted(AccountIntent $intent, Account $account, Member $by): bool
    {
        try {
            match ($intent) {
                AccountIntent::ReviseLegalIdentity => $account->reviseLegalIdentity(new LegalIdentity('SO-LEAN'), $by, new \DateTimeImmutable()),
                AccountIntent::ChangeLogo => $account->changeLogo(new File('https://storage.test/logo.png', 'png', 1024), $by, new \DateTimeImmutable()),
                AccountIntent::Relocate => $account->relocateTo(new Address(city: 'Strasbourg'), null, $by, new \DateTimeImmutable()),
            };
        } catch (NotFound) {
            return false;
        }

        return true;
    }
}
