<?php

declare(strict_types=1);

namespace App\Shared\Persistence;

use App\Shared\ServiceUnavailable;
use App\Shared\Typed;
use Aws\Ssm\Exception\SsmException;
use Aws\Ssm\SsmClient;

/**
 * The secrets that the accounts give the API. Each one is an SSM `SecureString` under the instance's path, encrypted
 * by the `aws/ssm` key (ADR 0125). SSM is outside the unit of work. A secret is put at a new version, which its
 * aggregate's item names, and it is read at that version only. So a commit that fails after the put keeps the former
 * version. When SSM refuses or fails, for instance because a role lacks the permission, the secrets are out of reach.
 */
final readonly class AccountSecrets
{
    private const array MISSING = ['ParameterNotFound', 'ParameterVersionNotFound'];
    private const int DELETED_AT_ONCE = 10;

    public function __construct(private SsmClient $ssm, private string $path)
    {
    }

    /**
     * @return int the version at which the secret is kept
     */
    public function put(string $account, string $name, #[\SensitiveParameter] string $secret): int
    {
        try {
            return Typed::int($this->ssm->putParameter(['Name' => $this->nameOf($account, $name), 'Value' => $secret, 'Type' => 'SecureString', 'Overwrite' => true])['Version']);
        } catch (SsmException $failure) {
            throw ServiceUnavailable::secrets($failure);
        }
    }

    public function find(string $account, string $name, int $version): ?string
    {
        try {
            return Typed::string(Typed::document($this->ssm->getParameter(['Name' => sprintf('%s:%d', $this->nameOf($account, $name), $version), 'WithDecryption' => true])['Parameter'])['Value'] ?? null);
        } catch (SsmException $failure) {
            return self::isMissing($failure) ? null : throw ServiceUnavailable::secrets($failure);
        }
    }

    /**
     * Every version of the secret, at once.
     */
    public function forget(string $account, string $name): void
    {
        try {
            $this->ssm->deleteParameter(['Name' => $this->nameOf($account, $name)]);
        } catch (SsmException $failure) {
            if (!self::isMissing($failure)) {
                throw ServiceUnavailable::secrets($failure);
            }
        }
    }

    /**
     * Every account's secrets, when the tables are recreated.
     */
    public function forgetEvery(): void
    {
        $names = [];
        foreach ($this->ssm->getPaginator('GetParametersByPath', ['Path' => $this->path, 'Recursive' => true]) as $page) {
            $names = [...$names, ...Typed::strings(array_column(Typed::documents($page['Parameters']), 'Name'))];
        }

        foreach (array_chunk($names, self::DELETED_AT_ONCE) as $chunk) {
            $this->ssm->deleteParameters(['Names' => $chunk]);
        }
    }

    private function nameOf(string $account, string $name): string
    {
        return sprintf('%s/%s/%s', $this->path, $account, $name);
    }

    private static function isMissing(SsmException $failure): bool
    {
        return in_array($failure->getAwsErrorCode(), self::MISSING, true);
    }
}
