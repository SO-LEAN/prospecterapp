<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Contract\Service\ConsultMember\ConsultMember;
use Prospecter\Core\Accounts\Contract\Service\ConsultMember\MemberResponse;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownReader;
use Prospecter\Core\PersonalData\Domain\Reader;
use Prospecter\Core\PersonalData\Domain\ReaderService;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Anticorruption layer of the API, in the scenarios (ADR 0045): PersonalData knows readers, Accounts publishes its members.
 */
final readonly class AccountsReaderService implements ReaderService
{
    public function __construct(private RefusalTranslatingHandler $consultMember)
    {
    }

    public function readerFrom(MemberId $id): Reader
    {
        $member = $this->memberOf($id);

        return new Reader($id, new AccountId($member->accountId), new PersonId($member->personId), $member->readsPersonalData);
    }

    private function memberOf(MemberId $id): MemberResponse
    {
        try {
            return ($this->consultMember)(new ConsultMember((string) $id));
        } catch (UseCaseRefused) {
            throw UnknownReader::identifiedBy($id);
        }
    }
}
