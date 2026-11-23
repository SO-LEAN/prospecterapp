<?php

declare(strict_types=1);

namespace App\Me\Mcp;

use App\Me\MyAccountReader;
use App\Me\Resource\AccountResource;
use App\Security\ToolAccess;
use App\Security\User;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Tells whom the AI serves: the member that its access token names, and their account, as `/api/me` reads them. It
 * also tells the member's right to the personal data. The AI has the same right as the member (ADR 0156).
 */
final readonly class WhoAmITool
{
    public function __construct(private ToolAccess $access, private Security $security, private MyAccountReader $myAccount)
    {
    }

    /**
     * @return array{member: string, account: ?string, readsPersonalData: bool}
     */
    #[McpTool(name: 'get-me', description: 'Get the Prospecter member you serve, their account, and whether they read personal data in clear. Otherwise, personal data come masked.', annotations: new ToolAnnotations(readOnlyHint: true))]
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
