<?php

namespace Tests\App\Form\DataTransformer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

use App\Form\DataTransformer\TwoFileFieldsTransformer;
use App\Service\System\PhpFileSystemAdapter;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\App\Base\TestCase;

#[Group('unit')]
class TwoFileFieldsTransformerTest extends TestCase
{
    private const array FIELDS = ['property', 'alternativeProperty'];

    public function target() : TwoFileFieldsTransformer
    {
        return parent::target();
    }

    public function setupArgs() : array
    {
        return array_merge([new PhpFileSystemAdapter()], self::FIELDS);
    }

    public function testTransform()
    {
        $value = ['transform'];
        $this->assertEquals($this->target()->transform($value), $value);
    }

    #[DataProvider('provideReverseTransform')]
    public function testReverseTransform($expected, $value)
    {
        $this->assertEquals($expected, $this->target()->reverseTransform($value));
    }

    /**
     * @return array
     */
    public static function provideReverseTransform(): array
    {
        $file = new UploadedFile(__dir__ . '/../../Stub/file.jpg', 'file.jpg');
        $link = __dir__ . '/../../Stub/link.jpg';

        return [
            '0 - nothing then nothing'   => [null, self::buildValue(null, null)],
            '1 - File then nothing'      => [$file, self::buildValue($file, null)],
            '2 - nothing then link' =>      [new UploadedFile($link, 'link.jpg'), self::buildValue(null, $link)],
            '3 - File then link'    =>      [$file, self::buildValue($file, $link)],
        ];
    }

    public function testTransformationFailedExceptionIsThrownWhenValueIsNotAnArrayOnReverseTransform()
    {
        $this->expectExceptionObject(new TransformationFailedException('Expected an array.'));
        $this->target()->reverseTransform(9);
    }

    public function testTransformationFailedExceptionIsThrownWhenBadUrlProvidedOnReverseTransform()
    {
        $this->expectExceptionObject(new TransformationFailedException('Cannot download file : "bad/url/provided"'));
        $this->target()->reverseTransform(self::buildValue(null, 'bad/url/provided'));
    }

    /**
     * @param $first
     * @param $second
     * @return array
     */
    private static function buildValue($first, $second): array
    {
        return [
            self::FIELDS[0] => $first,
            self::FIELDS[1] => $second,
        ];
    }
}
