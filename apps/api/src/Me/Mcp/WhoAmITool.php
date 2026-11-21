<?php

namespace App\Me\Mcp;

use App\Me\MyAccountReader;
use App\Me\Resource\AccountResource;
use App\Security\ToolAccess;
use App\Security\User;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Whom the AI serves: the member its access token names, and their account, as `/api/me` reads them, with their
 * right on the personal data, which the AI has as they do (ADR 0156).
 */
final readonly class WhoAmITool
{
    public function __construct(private ToolAccess $access, private Security $security, private MyAccountReader $myAccount)
    {
    }

    /**
     * @return array{member: string, account: ?string, readsPersonalData: bool}
     */
    #[McpTool(name: 'get-me', description: 'The member of Prospecter you serve, their account, and whether they read the personal data in clear (masked otherwise).', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(): array
    {
        $account = $this->myAccount->read($this->access->member()->memberId());

        return [
            'member' => $this->fullNameIn($account),
            'account' => $account->organization->legalIdentity->corporateName,
            'readsPersonalData' => $this->security->isGranted(User::PERSONAL_DATA),
        ];
    }

    private function fullNameIn(AccountResource $account): string
    {
        return implode(' ', array_filter([$account->name->firstName, $account->name->lastName]));
    }
}
