<?php

namespace App\Menu\Matcher;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PathInfoRegexVoter implements VoterInterface
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function matchItem(ItemInterface $item): ?bool
    {
        $regex = $item->getExtra('current_regex');

        if (null !== $regex && preg_match($regex, $this->requestStack->getCurrentRequest()->getPathInfo())) {
            return true;
        }

        return false;
    }
}
