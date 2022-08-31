<?php

namespace Cruxinator\Attachments\Tests;

use Cruxinator\Attachments\Tests\Fixtures\User;
use Cruxinator\LaravelAttachmentsMedia\Models\Picture;
use Cruxinator\LaravelAttachmentsMedia\Models\ResizablePicture;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;

class ResizablePictureTest extends TestCase
{
    use DatabaseTransactions;

    public function testFfmpegBase()
    {
        config(['attachments.ffmpeg' => 'ffmpeg/']);

        $expected = base_path('ffmpeg' . DIRECTORY_SEPARATOR);
        $actual = ResizablePicture::ffmpegBase();

        $this->assertEquals($expected, $actual);
    }

    public function testKeyAttachment()
    {
        $foo = new User(['name' => 'name']);
        $this->assertTrue($foo->save());

        $att = new ResizablePicture();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = '';
        $att->filetype = '';
        $att->filesize = 0;
        $att->group = 'aybabtu';
        $att->attachable()->associate($foo);
        $att->save();

        $key = $att->key;

        $actual = ResizablePicture::keyAttachment($foo, $key);
        $this->assertEquals($att->getKey(), $actual->getKey());
    }

    public function testOfProfileGoodSize()
    {
        $sizes = [
            'sample' => ['width' => 360, 'height' => 360, 'invert' => 0, 'aspect' => 1, 'rotate' => -1],
        ];

        config(['attachments.image.sizes' => $sizes]);

        $file = base_path('../../../../tests/resources/PNG_transparency_demonstration_1.png');
        $file = str_replace('/', DIRECTORY_SEPARATOR, $file);
        $main = File::get($file);
        File::shouldReceive('get')->andReturn($main);
        File::shouldReceive('extension')->andReturn('png');
        File::shouldReceive('isDirectory')->andReturn(true);
        File::shouldReceive('put')->andReturn(true);
        File::shouldReceive('size')->andReturn(30720);
        File::shouldReceive('mimeType')->andReturn('image/png');

        $att = new ResizablePicture();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = 'lain-cyberia-mix.png';
        $att->filetype = '';
        $att->filesize = 0;
        $att->attachable_type = '';
        $att->attachable_id = 0;
        $this->assertTrue($att->save());

        $trim = $att->ofProfile('sample');
        $this->assertTrue($trim instanceof Picture);
        $this->assertFalse($trim instanceof ResizablePicture);
        $this->assertEquals('lain-cyberia-mix.png', $trim->filename);
        $this->assertEquals(30720, $trim->filesize);
        $this->assertEquals('image/png', $trim->filetype);
        $this->assertEquals('360x360___0_1', $trim->key);

        // check original subordinate attachment is recycled
        $newTrim = $att->ofProfile('sample');
        $this->assertTrue($newTrim instanceof Picture);
        $this->assertFalse($newTrim instanceof ResizablePicture);
        $this->assertEquals($trim->getKey(), $newTrim->getKey(), 'Existing attachment not recycled on second call');
    }

    public function testOfProfileEmptySize()
    {
        $file = base_path('../../../../tests/resources/PNG_transparency_demonstration_1.png');
        $file = str_replace('/', DIRECTORY_SEPARATOR, $file);
        $main = File::get($file);
        File::shouldReceive('get')->andReturn($main);
        File::shouldReceive('extension')->andReturn('png');
        File::shouldReceive('isDirectory')->andReturn(true);
        File::shouldReceive('put')->andReturn(true);
        File::shouldReceive('size')->andReturn(30720);
        File::shouldReceive('mimeType')->andReturn('image/png');

        $att = new ResizablePicture();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = 'lain-cyberia-mix.png';
        $att->filetype = '';
        $att->filesize = 0;
        $att->attachable_type = '';
        $att->attachable_id = 0;
        $this->assertTrue($att->save());

        $trim = $att->ofProfile();
        $this->assertTrue($trim instanceof Picture);
        $this->assertFalse($trim instanceof ResizablePicture);
        $this->assertEquals('lain-cyberia-mix.png', $trim->filename);
        $this->assertEquals(30720, $trim->filesize);
        $this->assertEquals('image/png', $trim->filetype);
        $this->assertEquals('800x600__', $trim->key);

        // check original subordinate attachment is recycled
        $newTrim = $att->ofProfile();
        $this->assertTrue($newTrim instanceof Picture);
        $this->assertFalse($newTrim instanceof ResizablePicture);
        $this->assertEquals($trim->getKey(), $newTrim->getKey(), 'Existing attachment not recycled on second call');
    }

    public function testOfProfileBadSize()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('An Attempt to load profile didn\'t yield a valid sizes array. received data');

        $att = new ResizablePicture();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = 'lain-cyberia-mix.png';
        $att->filetype = '';
        $att->filesize = 0;
        $att->attachable_type = '';
        $att->attachable_id = 0;
        $this->assertTrue($att->save());

        $trim = $att->ofProfile('sample');
    }
}
