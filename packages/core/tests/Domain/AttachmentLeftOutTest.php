<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Policy\AttachmentLeftOut;

class AttachmentLeftOutTest extends TestCase
{
    private const int KILOBYTE = 1024;
    private const int MEGABYTE = 1024 * self::KILOBYTE;

    public function testAFileOfMegabytesIsToldInMegabytes(): void
    {
        $this->assertSame('Pièce jointe non importée : video.mp4 (25 Mo), à ouvrir dans Gmail', AttachmentLeftOut::mention('video.mp4', 25 * self::MEGABYTE));
    }

    public function testAFileUnderAMegabyteIsToldInKilobytes(): void
    {
        $this->assertSame('Pièce jointe non importée : devis.pdf (300 Ko), à ouvrir dans Gmail', AttachmentLeftOut::mention('devis.pdf', 300 * self::KILOBYTE));
    }
}
