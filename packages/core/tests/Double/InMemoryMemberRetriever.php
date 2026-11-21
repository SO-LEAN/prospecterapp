<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Application\Port\MemberRetriever;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\MemberResponse;
use Prospecter\Core\Accounts\Domain\Model\Member;
use Prospecter\Core\Accounts\Domain\Policy\AdministrationRight;
use Prospecter\Core\Accounts\Domain\Policy\PersonalDataRight;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads the members the in-memory repository saved, as the adapter reads the items of the table.
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
            $reminders = $state->value('reminders');
            $account = $this->database->account($member->accountId()->value);
            $accountName = null === $account ? null : StateOf::the($account)->value('legalIdentity')->corporateName;
            $members[$member->id()->value] = new MemberResponse($member->id()->value, $member->accountId()->value, $state->value('personId')->value, PersonalDataRight::isGrantedBy(...$state->value('roles')), AdministrationRight::isGrantedBy(...$state->value('roles')), $reminders->enabled ? $reminders->delay->value : null, $accountName);
        }

        return $members;
    }
}
