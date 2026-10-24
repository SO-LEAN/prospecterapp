<?php

namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Decides whether the signed-in member may carry out an intention on a resource of an account, from its read model
 * (ADR 0081): a resource of another account is unknown to them (404, `ApiAccessDeniedHandler`), an intention may
 * need the right to manage personal data (403). Whether its state offers it now is the business of the core, which
 * refuses it otherwise (409): a presenter shows a link only when both agree.
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

    public function __construct(private readonly AccessDecisionManagerInterface $accessDecisions)
    {
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $member = $token->getUser();

        if (!$member instanceof User) {
            return false;
        }

        if ($member->accountId() !== $this->accountOf($subject)) {
            $this->tellUnknown($vote);

            return false;
        }

        return !$this->needsThePersonalDataRight($attribute, $subject) || $this->accessDecisions->decide($token, [User::PERSONAL_DATA]);
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

    /**
     * @param TResource $subject
     */
    abstract protected function needsThePersonalDataRight(string $attribute, object $subject): bool;
}
