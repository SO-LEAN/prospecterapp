<?php

namespace App\Accounts\Persistence;

use App\Shared\Persistence\Table;
use Prospecter\Core\Accounts\Application\Port\MemberRetriever;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\MemberResponse;
use Prospecter\Core\Accounts\Domain\Model\Role;
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
        $members = [];

        foreach ($this->accountsTable->items(array_map(static fn (MemberId $id): array => [AccountsKeys::member($id->value), AccountsKeys::MEMBER], $ids)) as $item) {
            $document = $item['member'];
            $members[$document['id']] = new MemberResponse($document['id'], $document['accountId'], $document['personId'], PersonalDataRight::isGrantedBy(...array_map(Role::from(...), $document['roles'])));
        }

        return $members;
    }
}
