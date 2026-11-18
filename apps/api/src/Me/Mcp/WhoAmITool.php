<?php

namespace App\Me\Mcp;

use App\Me\MyAccountReader;
use App\Security\User;
use Mcp\Server\Capability\Attribute\McpTool;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * The proof of the spike #202: the member signed in, and their account.
 */
final readonly class WhoAmITool
{
    public function __construct(private Security $security, private MyAccountReader $myAccount)
    {
    }

    /**
     * @return array{member: string, account: ?string}
     */
    #[McpTool(name: 'qui-suis-je', description: 'Le membre de Prospecter connecté et son compte.')]
    public function __invoke(): array
    {
        $user = $this->security->getUser();
        \assert($user instanceof User);
        $account = $this->myAccount->read($user->memberId());

        return [
            'member' => trim(($account->name->firstName ?? '').' '.($account->name->lastName ?? '')),
            'account' => $account->organization->legalIdentity->corporateName,
        ];
    }
}
