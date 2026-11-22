<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\AccessDecision;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Who calls a tool of the MCP server, and what the attributes `#[IsGranted]` of the controller it adapts check
 * (ADR 0163): the role of the member, their access to a resource by its voter, refused as the endpoint refuses it.
 */
final readonly class ToolAccess
{
    public function __construct(private Security $security)
    {
    }

    public function member(): User
    {
        $member = $this->security->getUser();
        \assert($member instanceof User);

        return $member;
    }

    public function denyUnlessGranted(string $attribute, mixed $subject = null): void
    {
        $decision = new AccessDecision();

        if ($this->security->isGranted($attribute, $subject, $decision)) {
            return;
        }

        $denial = new AccessDeniedException($decision->getMessage());
        $denial->setAttributes([$attribute]);
        $denial->setSubject($subject);
        $denial->setAccessDecision($decision);

        throw $denial;
    }
}
