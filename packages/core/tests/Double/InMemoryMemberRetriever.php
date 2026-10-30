<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Application\Port\MemberRetriever;
use Prospecter\Core\Accounts\Contract\Service\ConsultMember\MemberResponse;
use Prospecter\Core\Accounts\Domain\Exception\UnknownMember;
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

    public function get(MemberId $id): MemberResponse
    {
        $member = $this->database->member((string) $id) ?? throw UnknownMember::identifiedBy($id);

        return new MemberResponse($member->id()->value, $member->accountId()->value, $member->personId()->value, PersonalDataRight::isGrantedBy(...$member->roles()));
    }
}
