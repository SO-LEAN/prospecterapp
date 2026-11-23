<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Decides from a resource's read model whether the signed-in member reaches it (ADR 0081). They reach their own
 * account's resources. They do not even learn that another account's resource exists (404,
 * `ApiAccessDeniedHandler`). The rights that an intent needs are roles on its controller. The resource's presenter
 * shows the intent's link only when the member holds them.
 *
 * @template TResource of object
 *
 * @extends Voter<string, TResource>
 */
abstract class AccountResourceVoter extends Voter
{
    /**
     * The vote's extra-data key that holds the 404's reason when the resource belongs to another account.
     */
    public const string UNKNOWN = 'unknown';

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $member = $token->getUser();

        if (!$member instanceof User) {
            return false;
        }

        if ($member->accountId() === $this->accountOf($subject)) {
            return true;
        }

        $this->tellUnknown($vote);

        return false;
    }

    private function tellUnknown(?Vote $vote): void
    {
        if (null !== $vote) {
            $vote->extraData[self::UNKNOWN] = $this->unknownReason();
        }
    }

    /**
     * @param TResource $subject
     */
    abstract protected function accountOf(object $subject): string;

    /**
     * The 404's reason, as the core names an unknown resource of this kind (`unknownOrganization`).
     */
    abstract protected function unknownReason(): string;
}
