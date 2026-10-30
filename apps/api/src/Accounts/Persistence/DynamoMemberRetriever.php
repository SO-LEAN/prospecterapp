<?php

namespace App\Accounts\Persistence;

use App\Shared\Persistence\Table;
use Prospecter\Core\Accounts\Application\Port\MemberRetriever;
use Prospecter\Core\Accounts\Contract\Service\ConsultMember\MemberResponse;
use Prospecter\Core\Accounts\Domain\Exception\UnknownMember;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Accounts\Domain\Policy\PersonalDataRight;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads the item `MEMBER#<id>` / `MEMBER` that `DynamoMemberRepository` writes as the other contexts know the
 * member, without restoring them (ADR 0088).
 */
final readonly class DynamoMemberRetriever implements MemberRetriever
{
    public function __construct(private Table $accountsTable)
    {
    }

    public function get(MemberId $id): MemberResponse
    {
        $item = $this->accountsTable->item(AccountsKeys::member($id->value), AccountsKeys::MEMBER) ?? throw UnknownMember::identifiedBy($id);
        $document = $item['member'];

        return new MemberResponse($document['id'], $document['accountId'], $document['personId'], PersonalDataRight::isGrantedBy(...array_map(Role::from(...), $document['roles'])));
    }
}
