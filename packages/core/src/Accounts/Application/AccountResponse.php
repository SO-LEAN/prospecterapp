<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application;

use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\Accounts\Domain\AccountIntent;
use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\SharedKernel\Application\FileResponse;

/**
 * My account: my person, the identity and logo of my organization, what I may change of it.
 */
final readonly class AccountResponse
{
    /**
     * @param list<string> $accountIntents names of what I may change of my organization: ReviseLegalIdentity, ChangeLogo
     */
    private function __construct(
        public string $personId,
        public ?string $organizationCorporateName,
        public ?string $organizationForm,
        public ?string $organizationType,
        public ?FileResponse $organizationLogo,
        public array $accountIntents,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $modifiedAt,
    ) {
    }

    public static function of(Account $myAccount, Member $me): self
    {
        $identity = $myAccount->legalIdentity();

        return new self(
            personId: $me->personId()->value,
            organizationCorporateName: $identity->corporateName,
            organizationForm: $identity->form,
            organizationType: $identity->type,
            organizationLogo: FileResponse::orNone($myAccount->logo()),
            accountIntents: array_map(static fn (AccountIntent $intent): string => $intent->name, $myAccount->possibleIntentsOf($me)),
            createdAt: $myAccount->createdAt(),
            modifiedAt: $myAccount->modifiedAt(),
        );
    }
}
