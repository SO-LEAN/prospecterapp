<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * openapi.json is committed (the front generates its types from it): it must describe the API as the code does.
 */
#[Group('api')]
class OpenApiContractTest extends KernelTestCase
{
    public function testCommittedContractMatchesTheCode(): void
    {
        $command = new Application(self::bootKernel())->find('nelmio:apidoc:dump');
        $tester = new CommandTester($command);
        $tester->execute(['--format' => 'json']);

        $this->assertJsonStringEqualsJsonFile(
            self::getContainer()->getParameter('kernel.project_dir').'/openapi.json',
            $tester->getDisplay(),
            'apps/api/openapi.json is out of date: run `make api-doc` and commit the result (it also regenerates the front types).',
        );
    }
}
