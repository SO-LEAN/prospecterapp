<?php

namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Decides whether the signed-in member reaches a resource, from its read model (ADR 0081): one of their account,
 * or one they do not even learn exists (404, `ApiAccessDeniedHandler`). The rights an intention needs are roles
 * on its controller, and the presenter of the resource shows its link only when they are granted.
 *
 * @template TResource of object
 *
 * @extends Voter<string, TResource>
 */
abstract class AccountResourceVoter extends Voter
{
    /**
     * Set in the extra data of the vote, the reason of a 404 when the resource belongs to another account.
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
     * The reason of the 404, as the core names an unknown resource of its kind (`unknownOrganization`).
     */
    abstract protected function unknownReason(): string;
}
