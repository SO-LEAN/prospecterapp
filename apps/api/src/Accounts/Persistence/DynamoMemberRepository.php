<?php

declare(strict_types=1);

namespace App\Accounts\Persistence;

use App\Shared\Persistence\Hydrator;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Accounts\Domain\Exception\UnknownMember;
use Prospecter\Core\Accounts\Domain\Model\Member;
use Prospecter\Core\Accounts\Domain\Model\Reminders;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Accounts\Domain\Repository\MemberRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The members in the Accounts table (ADR 0048). Each member is a document under `MEMBER#<id>` / `MEMBER`.
 */
final readonly class DynamoMemberRepository implements MemberRepository
{
    public function __construct(private Table $accountsTable)
    {
    }

    public function get(MemberId $id): Member
    {
        $item = $this->accountsTable->item(AccountsKeys::member($id->value), AccountsKeys::MEMBER) ?? throw UnknownMember::identifiedBy($id);
        $document = Typed::document($item['member']);

        return Hydrator::restore(Member::class, [
            'id' => new MemberId(Typed::string($document['id'])),
            'accountId' => new AccountId(Typed::string($document['accountId'])),
            'personId' => new PersonId(Typed::string($document['personId'])),
            'roles' => array_map(Role::from(...), Typed::strings($document['roles'])),
            'reminders' => RemindersDocument::restore($document['reminders'] ?? null),
        ]);
    }

    public function save(Member $member): void
    {
        $state = Hydrator::stateOf($member);

        $this->accountsTable->transact([$this->accountsTable->putting(['PK' => AccountsKeys::member($member->id()->value), 'SK' => AccountsKeys::MEMBER, 'member' => [
            'id' => Typed::object($state['id'], MemberId::class)->value,
            'accountId' => Typed::object($state['accountId'], AccountId::class)->value,
            'personId' => Typed::object($state['personId'], PersonId::class)->value,
            'roles' => array_map(static fn (Role $role): string => $role->value, Typed::objects($state['roles'], Role::class)),
            'reminders' => RemindersDocument::of(Typed::object($state['reminders'], Reminders::class)),
        ]])]);
    }
}
