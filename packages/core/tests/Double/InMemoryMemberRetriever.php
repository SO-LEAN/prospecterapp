<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Application\Port\MemberRetriever;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\MemberResponse;
use Prospecter\Core\Accounts\Domain\Model\Member;
use Prospecter\Core\Accounts\Domain\Model\ProfileId;
use Prospecter\Core\Accounts\Domain\Model\Reminders;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Accounts\Domain\Policy\AdministrationRight;
use Prospecter\Core\Accounts\Domain\Policy\PersonalDataRight;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads the members that the in-memory repository saved, as the adapter reads the table's items.
 */
final readonly class InMemoryMemberRetriever implements MemberRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    /**
     * @param list<MemberId> $ids
     */
    public function ofIds(array $ids): array
    {
        $found = array_filter(array_map(fn (MemberId $id): ?Member => $this->database->member($id->value), $ids));
        $members = [];

        foreach ($found as $member) {
            $state = StateOf::the($member);
            $reminders = $state->object('reminders', Reminders::class);
            $account = $this->database->account($member->accountId()->value);
            $accountName = null === $account ? null : StateOf::the($account)->object('legalIdentity', LegalIdentity::class)->corporateName;
            $members[$member->id()->value] = new MemberResponse($member->id()->value, $member->accountId()->value, $state->object('personId', ProfileId::class)->value, PersonalDataRight::isGrantedBy(...$state->listOf('roles', Role::class)), AdministrationRight::isGrantedBy(...$state->listOf('roles', Role::class)), $reminders->enabled ? $reminders->delay->value : null, $accountName);
        }

        return $members;
    }
}
