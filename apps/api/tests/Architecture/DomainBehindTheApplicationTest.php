<?php

namespace Tests\App\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * The API delivers the use cases through their requests and responses only (ADR 0035): controllers,
 * inputs, resources, presenters and security never see a class of the domain. Only the adapters that
 * implement its ports do: persistence, anticorruption layers, gateways, and the fixtures that arrange it.
 */
class DomainBehindTheApplicationTest extends TestCase
{
    private const string DOMAIN = '/^use Prospecter\\\\Core\\\\\w+\\\\Domain\\\\[\w\\\\]+;/m';

    private const array ADAPTERS = [
        'Prospecting/AccountsProspectors.php',
        'Prospecting/PersonalDataKnownContacts.php',
        'PersonalData/ProspectingOrganizations.php',
        'PersonalData/AccountsReaders.php',
        'Shared/GeoplateformeGeoLocation.php',
        'Shared/PrivateStorageClient.php',
        'Demo/DemoData.php',
    ];

    public function testOnlyTheAdaptersOfThePortsUseTheDomain(): void
    {
        $this->assertSame([], $this->deliveryFilesUsingTheDomain());
    }

    /**
     * @return list<string>
     */
    private function deliveryFilesUsingTheDomain(): array
    {
        $root = dirname(__DIR__, 2).'/src/';
        $files = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $path = substr($file->getPathname(), strlen($root));

            if (!$this->isAnAdapter($path) && preg_match(self::DOMAIN, (string) file_get_contents($file->getPathname()))) {
                $files[] = $path;
            }
        }

        sort($files);

        return $files;
    }

    private function isAnAdapter(string $path): bool
    {
        return str_contains($path, '/Persistence/') || in_array($path, self::ADAPTERS, true);
    }
}
