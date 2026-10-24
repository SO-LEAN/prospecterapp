<?php

namespace App\Prospecting;

use App\Security\User;
use Prospecter\Core\Prospecting\Application\TimelineResponse;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * What the signed-in member may do with a timeline the API already let them read, the one of a contact of their
 * account or of one of its organizations (ADR 0081): what is said with a contact is personal data, noting it
 * needs the right to manage them.
 *
 * @extends Voter<string, TimelineResponse>
 */
final class TimelineVoter extends Voter
{
    public const string NOTE_EXCHANGE = 'NoteExchange';

    public function __construct(private readonly AccessDecisionManagerInterface $accessDecisions)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof TimelineResponse && self::NOTE_EXCHANGE === $attribute;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        return $this->accessDecisions->decide($token, [User::PERSONAL_DATA]);
    }
}
