<?php

namespace Cruxinator\Attachments\Tests;

use Cruxinator\Attachments\Models\Attachment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Adapter\Local;
use Mockery as m;

class AttachmentAttributesTest extends TestCase
{
    use DatabaseTransactions;

    public function testToArray()
    {
        $att = new Attachment();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = 'lain-cyberia-mix.png';
        $att->filetype = '';
        $att->filesize = 0;
        $att->attachable_type = '';
        $att->attachable_id = 0;
        $this->assertTrue($att->save());
        $att->refresh();
        $this->assertTrue($att->exists);

        $format = $att->getDateFormat();

        $expected = [
            'id' => $att->getKey(),
            'type' => Attachment::class,
            'uuid' => $att->uuid,
            'disk' => 'local',
            'filepath' => '',
            'filename' => 'lain-cyberia-mix.png',
            'filetype' => '',
            'filesize' => '0',
            'key' => $att->key,
            'group' => null,
            'title' => null,
            'description' => null,
            'preview_url' => null,
            'attachable_type' => '',
            'attachable_id' => '0',
            'metadata' => null,
            'created_at' => $att->created_at->format($format),
            'updated_at' => $att->updated_at->format($format),
            'url' => $att->url,
            'url_inline' => $att->url_inline,
        ];
        $actual = $att->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function testToArrayFromS3()
    {
        $att = new Attachment();
        $att->disk = 's3';
        $att->filepath = '';
        $att->filename = 'lain-cyberia-mix.png';
        $att->filetype = '';
        $att->filesize = 0;
        $att->attachable_type = '';
        $att->attachable_id = 0;
        $this->assertTrue($att->save());
        $att->refresh();
        $this->assertTrue($att->exists);

        $adapt = m::mock(Local::class);

        Storage::shouldReceive('disk->url')->andReturn('https://dummy.url')->times(2);
        Storage::shouldReceive('disk->getDriver->getAdapter')->andReturn($adapt)->atLeast(1);

        $format = $att->getDateFormat();

        $expected = [
            'id' => $att->getKey(),
            'type' => Attachment::class,
            'uuid' => $att->uuid,
            'disk' => 's3',
            'filepath' => '',
            'filename' => 'lain-cyberia-mix.png',
            'filetype' => '',
            'filesize' => '0',
            'key' => $att->key,
            'group' => null,
            'title' => null,
            'description' => null,
            'preview_url' => null,
            'attachable_type' => '',
            'attachable_id' => '0',
            'metadata' => null,
            'created_at' => $att->created_at->format($format),
            'updated_at' => $att->updated_at->format($format),
            'url' => 'https://dummy.url',
            'url_inline' => 'https://dummy.url',
        ];
        $actual = $att->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function testGetTemporaryUrl()
    {
        config(['app.key' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa']);
        $now = Carbon::create(2022, 10, 9, 8, 7, 6);
        Carbon::setTestNow($now);

        $expire = now()->addHours(2);

        $att = new Attachment();
        $att->uuid = 'deadbeef';
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = 'lain-cyberia-mix.png';
        $att->filetype = '';
        $att->filesize = 0;
        $att->attachable_type = '';
        $att->attachable_id = 0;
        $this->assertTrue($att->save());
        $att->refresh();
        $this->assertTrue($att->exists);

        $actual = $att->getTemporaryUrl($expire);
        $this->assertNotNull($actual);
    }

    public function testGetDiskName()
    {
        $att = new Attachment();
        $att->uuid = 'deadbeef';
        $att->disk = 'local';
        $att->filepath = 'dummy-filepath';

        $ref = new \ReflectionClass($att);
        $method = $ref->getMethod('getDiskName');
        $method->setAccessible(true);
        
        $actual = $method->invoke($att);
        $this->assertEquals($att->filepath, $actual);
    }
}
