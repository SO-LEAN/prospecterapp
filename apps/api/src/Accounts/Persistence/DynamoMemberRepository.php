<?php

namespace App\Accounts\Persistence;

use App\Shared\Persistence\Hydrator;
use App\Shared\Persistence\Table;
use Prospecter\Core\Accounts\Domain\Exception\UnknownMember;
use Prospecter\Core\Accounts\Domain\Model\Member;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Accounts\Domain\Repository\MemberRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The members in the table of Accounts (ADR 0048): `MEMBER#<id>` / `MEMBER`, the member as a document.
 */
final readonly class DynamoMemberRepository implements MemberRepository
{
    private const string MEMBER = 'MEMBER';

    public function __construct(private Table $accountsTable)
    {
    }

    public function get(MemberId $id): Member
    {
        $item = $this->accountsTable->item(self::keyOf($id), self::MEMBER) ?? throw UnknownMember::identifiedBy($id);
        $document = $item['member'];

        return Hydrator::restore(Member::class, [
            'id' => new MemberId($document['id']),
            'accountId' => new AccountId($document['accountId']),
            'personId' => new PersonId($document['personId']),
            'roles' => array_map(Role::from(...), $document['roles']),
        ]);
    }

    public function save(Member $member): void
    {
        $state = Hydrator::stateOf($member);

        $this->accountsTable->transact([$this->accountsTable->putting(['PK' => self::keyOf($member->id()), 'SK' => self::MEMBER, 'member' => [
            'id' => (string) $state['id'],
            'accountId' => (string) $state['accountId'],
            'personId' => (string) $state['personId'],
            'roles' => array_map(static fn (Role $role): string => $role->value, $state['roles']),
        ]])]);
    }

    private static function keyOf(MemberId $id): string
    {
        return self::MEMBER.'#'.$id->value;
    }
}
