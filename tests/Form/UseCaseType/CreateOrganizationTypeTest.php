<?php

namespace Tests\App\Form\UseCaseType;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use SplFileInfo;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use App\Form\EasyImportFileType;
use Prophecy\Prophecy\ObjectProphecy;
use App\Service\System\PhpFileSystemAdapter;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use App\Form\UseCaseType\CreateOrganizationType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

#[Group('unit')]
#[AllowMockObjectsWithoutExpectations]
class CreateOrganizationTypeTest extends TypeTestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy
     */
    private $fileSystemAdapter;

    #[DataProvider('provideSubmitValidData')]
    public function testSubmitValidData(array $expected, array $formData, ?UploadedFile $link = null): void
    {

        $this->mockFileSystem($link);
        $form = $this->factory->create(CreateOrganizationType::class);

        $form->submit($formData);
        $view = $form->createView();
        $children = $view->children;

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $form->getData());

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    /**
     * @return array
     */
    public static function provideSubmitValidData()
    {
        $file = new UploadedFile(realpath(__dir__ . '/../../Stub/file.jpg'), 'file.jpg');
        $linkFile = new UploadedFile(realpath(__dir__ . '/../../Stub/link.jpg'), 'link.jpg');

        $common = [
            'corporateName' => 'corporate name',
            'form'          => 'GMBH',
            'type'          => 'ESN',
            'language'      => 'FR',
            'email'         => 'EMAIL',
            'phoneNumber'   => '0388888888',
            'street'        => 'fighter street',
            'postalCode'    => '97220',
            'city'          => 'Trinité',
            'country'       => 'MQ',
            'observations'  => 'obs.',
        ];

        return [
            '0 - without logo' => [$common + ['logo' => null], $common, null],
            '1 - regular file' => [$common + ['logo' => self::buildImageData($file)], $common+ ['logo' => ['url' => '', 'image' => self::buildImageData($file)]], null],
            '2 - file link'    => [$common + ['logo' => $linkFile], $common + ['logo' => ['orUrl' => 'http://fakelink.fr/link.jpg', 'image' => '']], $linkFile],
        ];
    }

    /**
     * @return array
     */
    protected function getExtensions(): array
    {
        $this->fileSystemAdapter = $this->prophesize(PhpFileSystemAdapter::class);
        $childType = new EasyImportFileType($this->fileSystemAdapter->reveal());

        return array_merge(parent::getExtensions(), [new ValidatorExtension(Validation::createValidator()), new PreloadedExtension([EasyImportFileType::class => $childType], [])]);
    }

    /**
     * @param SplFileInfo $file
     * @return array
     */
    private static function buildImageData(SplFileInfo $file)
    {
        return [
            'name'     => $file->getFilename(),
            'type'     => 'image/fake',
            'size'     => $file->getSize(),
            'tmp_name' => '/fake/tmp/name',
            'error'    => UPLOAD_ERR_OK,
        ];
    }

    /**
     * @param UploadedFile $link
     */
    private function mockFileSystem(?UploadedFile $link = null): void
    {
        $this->fileSystemAdapter->tempnam(Argument::any(), Argument::any())->willReturn($link ? $link->getPathname() : $link);
        $this->fileSystemAdapter->copy(Argument::any(), Argument::any(), Argument::any())->willReturn(true);
    }
}
