<?php

declare(strict_types=1);

namespace App\Shared\Persistence;

use App\Shared\ServiceUnavailable;
use App\Shared\Typed;
use Aws\Ssm\Exception\SsmException;
use Aws\Ssm\SsmClient;

/**
 * The secrets the accounts give the api, each a `SecureString` of SSM under the path of the instance, encrypted by
 * the key `aws/ssm` (ADR 0125). SSM is out of the unit of work: a secret is put at a new version, which the item
 * of its aggregate names, and read at that version only, so that a commit failing after the put keeps the former one.
 * SSM refusing or failing, a role whose permissions do not allow it for instance, leaves the secrets out of reach.
 */
final readonly class AccountSecrets
{
    private const array MISSING = ['ParameterNotFound', 'ParameterVersionNotFound'];
    private const int DELETED_AT_ONCE = 10;

    public function __construct(private SsmClient $ssm, private string $path)
    {
    }

    /**
     * @return int the version it is kept at
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
     * Every version of it, at once.
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
     * The secrets of every account, as the tables are recreated.
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
