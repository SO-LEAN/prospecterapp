<?php

namespace App\Me\Mcp;

use App\Me\MyAccountReader;
use App\Me\Resource\AccountResource;
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
    public function __construct(private Security $security, private MyAccountReader $myAccount)
    {
    }

    /**
     * @return array{member: string, account: ?string, readsPersonalData: bool}
     */
    #[McpTool(name: 'qui-suis-je', description: 'Le membre de Prospecter que tu sers, son compte, et s\'il lit les données personnelles en clair (sinon, masquées).', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(): array
    {
        $account = $this->myAccount->read($this->member()->memberId());

        return [
            'member' => $this->fullNameIn($account),
            'account' => $account->organization->legalIdentity->corporateName,
            'readsPersonalData' => $this->security->isGranted(User::PERSONAL_DATA),
        ];
    }

    private function member(): User
    {
        $member = $this->security->getUser();
        \assert($member instanceof User);

        return $member;
    }

    private function fullNameIn(AccountResource $account): string
    {
        return implode(' ', array_filter([$account->name->firstName, $account->name->lastName]));
    }
}
