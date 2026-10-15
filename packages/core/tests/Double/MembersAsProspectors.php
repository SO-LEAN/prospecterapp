<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Domain\Exception\UnknownMember;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownProspector;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\Prospecting\Domain\Prospectors;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The prospectors read from the members of the accounts, like the anticorruption layer of the API.
 */
final readonly class MembersAsProspectors implements Prospectors
{
    public function __construct(private MemberRepository $members)
    {
    }

    public function get(MemberId $id): Prospector
    {
        try {
            return new Prospector($id, $this->members->get($id)->accountId());
        } catch (UnknownMember) {
            throw UnknownProspector::identifiedBy($id);
        }
    }
}
