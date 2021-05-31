<?php

namespace Tests\App\Form\DataTransformer;


use App\Form\DataTransformer\TwoFileFieldsTransformer;
use App\Service\System\PhpFileSystemAdapter;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\App\Base\TestCase;

/**
 * @group unit
 */
class TwoFileFieldsTransformerTest extends TestCase
{
    /**
     * @var array
     */
    private $fields =  [
        'property',
        'alternativeProperty',
    ];

    public function target() : TwoFileFieldsTransformer
    {
        return parent::target();
    }

    public function setupArgs() : array
    {
        return array_merge([new PhpFileSystemAdapter()], $this->fields);
    }

    public function testTransform()
    {
        $value = ['transform'];
        $this->assertEquals($this->target()->transform($value), $value);
    }

    /**
     * @param $expected
     * @param $value
     *
     * @dataProvider provideReverseTransform
     */
    public function testReverseTransform($expected, $value)
    {
        $this->assertEquals($expected, $this->target()->reverseTransform($value));
    }

    /**
     * @return array
     */
    public function provideReverseTransform(): array
    {
        $file = new UploadedFile(__dir__ . '/../../Stub/file.jpg', 'file.jpg');
        $link = __dir__ . '/../../Stub/link.jpg';

        return [
            '0 - nothing then nothing'   => [null, $this->buildValue(null, null)],
            '1 - File then nothing'      => [$file, $this->buildValue($file, null)],
            '2 - nothing then link' =>      [new UploadedFile($link, 'link.jpg'), $this->buildValue(null, $link)],
            '3 - File then link'    =>      [$file, $this->buildValue($file, $link)],
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
        $this->target()->reverseTransform($this->buildValue(null, 'bad/url/provided'));
    }

    /**
     * @param $first
     * @param $second
     * @return array
     */
    private function buildValue($first, $second): array
    {
        return [
            $this->fields[0] => $first,
            $this->fields[1] => $second,
        ];
    }
}
