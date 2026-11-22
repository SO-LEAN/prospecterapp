<?php

declare(strict_types=1);

namespace App\Accounts\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\Accounts\Application\Port\MemberRetriever;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\MemberResponse;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Accounts\Domain\Policy\AdministrationRight;
use Prospecter\Core\Accounts\Domain\Policy\PersonalDataRight;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads the items `MEMBER#<id>` / `MEMBER` that `DynamoMemberRepository` writes as the other contexts know the
 * members, in one batch, without restoring them (ADR 0088, 0089).
 */
final readonly class DynamoMemberRetriever implements MemberRetriever
{
    public function __construct(private Table $accountsTable)
    {
    }

    /**
     * @param list<MemberId> $ids
     */
    public function ofIds(array $ids): array
    {
        $documents = array_map(static fn (array $item): array => $item['member'], $this->accountsTable->items(array_map(static fn (MemberId $id): array => [AccountsKeys::member($id->value), AccountsKeys::MEMBER], $ids)));
        $accountNames = $this->accountNamesOf($documents);
        $members = [];

        foreach ($documents as $document) {
            $roles = array_map(Role::from(...), $document['roles']);
            $reminders = RemindersDocument::restore($document['reminders'] ?? null);
            $members[$document['id']] = new MemberResponse($document['id'], $document['accountId'], $document['personId'], PersonalDataRight::isGrantedBy(...$roles), AdministrationRight::isGrantedBy(...$roles), $reminders->enabled ? $reminders->delay->value : null, $accountNames[$document['accountId']] ?? null);
        }

        return $members;
    }

    /**
     * The corporate names of the accounts of these members, read once each.
     *
     * @param list<array<string, mixed>> $members
     *
     * @return array<string, ?string> by the id of the account
     */
    private function accountNamesOf(array $members): array
    {
        $accountIds = array_values(array_unique(array_map(static fn (array $member): string => $member['accountId'], $members)));
        $names = [];
        foreach ($this->accountsTable->items(array_map(static fn (string $id): array => [AccountsKeys::account($id), AccountsKeys::ACCOUNT], $accountIds)) as $item) {
            $names[$item['account']['id']] = Documents::legalIdentityOf($item['account']['legalIdentity'])->corporateName;
        }

        return $names;
    }
}
