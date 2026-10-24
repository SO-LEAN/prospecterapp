<?php

namespace App\Security;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Whether a resource shows the link of an intention (ADR 0027, 0081): its state offers it now, as the core says,
 * and the signed-in member may carry it out, as its voter decides; a link shown is a call accepted.
 */
final readonly class IntentLinks
{
    public function __construct(private AuthorizationCheckerInterface $access)
    {
    }

    /**
     * @param list<string> $offered the intentions the state of the resource offers now, by name
     */
    public function shows(string $intent, object $resource, array $offered): bool
    {
        return in_array($intent, $offered, true) && $this->access->isGranted($intent, $resource);
    }
}
