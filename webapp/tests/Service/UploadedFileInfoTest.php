<?php

namespace Tests\App\Service;

use App\Service\UploadedFileInfo;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Group('unit')]
class UploadedFileInfoTest extends TestCase
{
    private string $temporaryPath;

    protected function setUp(): void
    {
        // like PHP uploads: a temporary file without extension
        $this->temporaryPath = tempnam(sys_get_temp_dir(), 'php');
        copy(__DIR__.'/../../assets/images/solean.png', $this->temporaryPath);
    }

    protected function tearDown(): void
    {
        @unlink($this->temporaryPath);
    }

    public function testExtensionIsGuessedFromContent(): void
    {
        $file = new UploadedFileInfo(new UploadedFile($this->temporaryPath, 'logo.jpg', test: true));

        $this->assertSame('png', $file->getExtension());
    }

    public function testExtensionFallsBackToClientNameWhenContentIsUnknown(): void
    {
        file_put_contents($this->temporaryPath, '');

        $file = new UploadedFileInfo(new UploadedFile($this->temporaryPath, 'notes.md', test: true));

        $this->assertSame('md', $file->getExtension());
    }

    public function testKeepsPathAndSizeOfTheUploadedFile(): void
    {
        $file = new UploadedFileInfo(new UploadedFile($this->temporaryPath, 'logo.png', test: true));

        $this->assertSame($this->temporaryPath, $file->getPathname());
        $this->assertSame(filesize($this->temporaryPath), $file->getSize());
    }
}
