<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Model\ResemblanceKey;

class ResemblanceKeyTest extends TestCase
{
    public function testTheNameAndTheCityAreComparedWithoutCaseNorAccents(): void
    {
        $this->assertEquals(ResemblanceKey::of('Électricité Rhône', 'Lyon'), ResemblanceKey::of('ELECTRICITE RHONE', 'LYON'));
    }

    public function testALegalFormDoesNotCount(): void
    {
        $this->assertEquals(ResemblanceKey::of('Acme', 'Lyon'), ResemblanceKey::of('Acme SAS', 'Lyon'));
    }

    public function testALegalFormWrittenWithPointsDoesNotCountEither(): void
    {
        $this->assertEquals(ResemblanceKey::of('Acme', 'Lyon'), ResemblanceKey::of('Acme S.A.R.L.', 'Lyon'));
    }

    public function testWithoutACityThereIsNoKey(): void
    {
        $this->assertNull(ResemblanceKey::of('Acme', null));
    }

    public function testWithoutANameBesideItsLegalFormThereIsNoKey(): void
    {
        $this->assertNull(ResemblanceKey::of('S.A.S.', 'Lyon'));
    }

    public function testTheSameNameInAnotherCityDoesNotResemble(): void
    {
        $this->assertNotEquals(ResemblanceKey::of('Acme', 'Lyon'), ResemblanceKey::of('Acme', 'Marseille'));
    }
}
