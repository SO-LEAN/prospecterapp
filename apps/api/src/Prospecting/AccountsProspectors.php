<?php

namespace App\Prospecting;

use Prospecter\Core\Accounts\Domain\Exception\UnknownMember;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownProspector;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\Prospecting\Domain\Prospectors;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Anticorruption layer: Prospecting knows prospectors, Accounts knows members. The only class that sees
 * both contexts (ADR 0030).
 */
final readonly class AccountsProspectors implements Prospectors
{
    public function __construct(private MemberRepository $members)
    {
    }

    public function get(MemberId $id): Prospector
    {
        try {
            $member = $this->members->get($id);
        } catch (UnknownMember) {
            throw UnknownProspector::identifiedBy($id);
        }

        return new Prospector($id, $member->accountId(), $member->readsPersonalData());
    }
}
