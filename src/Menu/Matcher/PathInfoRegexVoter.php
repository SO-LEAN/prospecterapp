<?php

namespace App\Menu\Matcher;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PathInfoRegexVoter implements VoterInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function matchItem(ItemInterface $item)
    {
        $regex = $item->getExtra('current_regex');

        if (null !== $regex && preg_match($regex, $this->requestStack->getCurrentRequest()->getPathInfo())) {
            return true;
        }

        return false;
    }
}
