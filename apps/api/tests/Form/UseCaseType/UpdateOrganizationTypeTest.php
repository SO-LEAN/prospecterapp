<?php

namespace Tests\App\Form\UseCaseType;

use App\Form\EasyImportFileType;
use App\Form\UseCaseType\UpdateOrganizationType;
use App\Service\System\PhpFileSystemAdapter;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validation;

#[Group('unit')]
#[AllowMockObjectsWithoutExpectations]
class UpdateOrganizationTypeTest extends TypeTestCase
{
    use ProphecyTrait;

    private ?ObjectProphecy $fileSystemAdapter = null;

    #[DataProvider('provideSubmitValidData')]
    public function testSubmitValidData(array $expected, array $formData, array $old, ?UploadedFile $link = null): void
    {
        $this->mockFileSystem($link);
        $form = $this->factory->create(UpdateOrganizationType::class, $old);

        $form->submit($formData, false);
        $view = $form->createView();
        $children = $view->children;

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $form->getData());

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    /**
     * @return array<string, array<string, string|mixed[]>[]|null[]|UploadedFile[]|array<string, UploadedFile|string|array<string, string>>[]>
     */
    public static function provideSubmitValidData(): array
    {
        $file = new UploadedFile(realpath(__DIR__.'/../../Stub/file.jpg'), 'file.jpg');
        $linkFile = new UploadedFile(realpath(__DIR__.'/../../Stub/link.jpg'), 'link.jpg');

        $input = [
            'id' => 'id',
            'corporateName' => 'old name',
            'form' => 'GMBH',
            'type' => 'Consulting',
            'language' => 'DE',
            'email' => 'old email',
            'phoneNumber' => 'old 0388888888',
            'street' => 'old street',
            'postalCode' => 'old cp',
            'city' => 'old city',
            'country' => 'FR',
            'observations' => 'old observation',
        ];

        $common = [
            'corporateName' => 'corporate name',
            'form' => 'GMBH',
            'type' => 'Consulting',
            'language' => 'FR',
            'email' => 'EMAIL',
            'phoneNumber' => '0388888888',
            'street' => 'fighter street',
            'postalCode' => '97220',
            'city' => 'Trinité',
            'country' => 'MQ',
            'observations' => 'obs.',
        ];

        return [
            '0 - without logo' => [$common + ['id' => 'id'], $common, $input, null],
            '1 - regular file' => [$common + ['logo' => self::buildImageData($file), 'id' => 'id'], $common + ['logo' => ['url' => '', 'image' => self::buildImageData($file)]], $input, null],
            '2 - file link' => [$common + ['logo' => $linkFile, 'id' => 'id'], $common + ['logo' => ['orUrl' => 'http://fakelink.fr/link.jpg', 'image' => '']], $input, $linkFile],
        ];
    }

    protected function getExtensions(): array
    {
        $this->fileSystemAdapter = $this->prophesize(PhpFileSystemAdapter::class);
        $childType = new EasyImportFileType($this->fileSystemAdapter->reveal());

        return array_merge(parent::getExtensions(), [new ValidatorExtension(Validation::createValidator()), new PreloadedExtension([EasyImportFileType::class => $childType], [])]);
    }

    /**
     * @return array<string, string|int|bool>
     */
    private static function buildImageData(\SplFileInfo $file): array
    {
        return [
            'name' => $file->getFilename(),
            'type' => 'image/fake',
            'size' => $file->getSize(),
            'tmp_name' => '/fake/tmp/name',
            'error' => UPLOAD_ERR_OK,
        ];
    }

    private function mockFileSystem(?UploadedFile $link = null): void
    {
        $this->fileSystemAdapter->tempnam(Argument::any(), Argument::any())->willReturn($link ? $link->getPathname() : $link);
        $this->fileSystemAdapter->copy(Argument::any(), Argument::any(), Argument::any())->willReturn(true);
    }
}
