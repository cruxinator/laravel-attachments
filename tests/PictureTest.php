<?php

namespace Cruxinator\Attachments\Tests;

use Cruxinator\LaravelAttachmentsMedia\Models\Picture;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;

class PictureTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetHtml()
    {
        $att = new Picture();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = 'lain-cyberia-mix.png';
        $att->filetype = '';
        $att->filesize = 0;
        $att->attachable_type = '';
        $att->attachable_id = 0;
        $this->assertTrue($att->save());

        $expected = '<img class="img-responsive" src="'.$att->url.'" alt style="min-width: 60px; min-height: 60px;">';
        $actual = $att->getHtml();

        $this->assertEquals($expected, $actual);
    }

    public function testLuminance()
    {
        $file = base_path('../../../../tests/resources/PNG_transparency_demonstration_1.png');
        $file = str_replace('/', DIRECTORY_SEPARATOR, $file);
        $main = File::get($file);
        File::shouldReceive('get')->andReturn($main);

        $att = new Picture();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = 'lain-cyberia-mix.png';
        $att->filetype = '';
        $att->filesize = 0;
        $att->attachable_type = '';
        $att->attachable_id = 0;
        $this->assertTrue($att->save());

        $expected = 127;
        $actual = $att->luminance;
        $this->assertEquals($expected, $actual, 'Unexpected luminance result');
    }
}
