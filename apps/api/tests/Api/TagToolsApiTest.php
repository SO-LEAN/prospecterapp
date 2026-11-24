<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ActsAsItsEndpoint;
use Tests\App\Base\ApiTestCase;

/**
 * An AI puts the tags on the contacts and the organizations and takes them off, as its member does on the screens
 * (ADR 0163).
 */
#[Group('api')]
class TagToolsApiTest extends ApiTestCase
{
    use ActsAsItsEndpoint;

    public function testTheAiTagsAContact(): void
    {
        $ada = $this->ada();

        $tags = $this->assertTheToolActsAsItsEndpoint('tag-contact', ['id' => $ada, 'tags' => ['#PHP']]);

        $this->assertContains('#PHP', array_column(self::recordsAt($tags, 'tags'), 'name'));
    }

    public function testTheAiTakesATagOffAContact(): void
    {
        $ada = $this->adaTagged('#PHP');

        $tags = $this->assertTheToolActsAsItsEndpoint('untag-contact', ['id' => $ada, 'tag' => 'PHP']);

        $this->assertNotContains('#PHP', array_column(self::recordsAt($tags, 'tags'), 'name'));
    }

    public function testTheAiTagsAnOrganization(): void
    {
        $babbage = $this->babbage();

        $tags = $this->assertTheToolActsAsItsEndpoint('tag-organization', ['id' => $babbage, 'tags' => ['#ESN']]);

        $this->assertContains('#ESN', array_column(self::recordsAt($tags, 'tags'), 'name'));
    }

    public function testTheAiTakesATagOffAnOrganization(): void
    {
        $babbage = $this->babbage();
        $this->requestJson('POST', '/api/organizations/'.$babbage.'/tags', ['tags' => ['#ESN']]);

        $tags = $this->assertTheToolActsAsItsEndpoint('untag-organization', ['id' => $babbage, 'tag' => 'ESN']);

        $this->assertNotContains('#ESN', array_column(self::recordsAt($tags, 'tags'), 'name'));
    }

    /**
     * Ada Lovelace, carrying this tag put by the prospector.
     */
    private function adaTagged(string $tag): string
    {
        $ada = $this->ada();
        $this->requestJson('POST', '/api/contacts/'.$ada.'/tags', ['tags' => [$tag]]);

        return $ada;
    }

    /**
     * Ada Lovelace, the contact of the demonstration, at Babbage & Co.
     */
    private function ada(): string
    {
        $this->login();

        return self::stringAt($this->requestJson('GET', '/api/contacts?q=Lovelace'), 'items', 0, 'id');
    }

    /**
     * Babbage & Co, the organization of the demonstration.
     */
    private function babbage(): string
    {
        $this->login();

        return self::stringAt($this->requestJson('GET', '/api/organizations?q=Babbage'), 'items', 0, 'id');
    }
}
