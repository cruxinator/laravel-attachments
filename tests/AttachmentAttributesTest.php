<?php


namespace Cruxinator\Attachments\Tests;


use Cruxinator\Attachments\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

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
            'created_at' => $att->created_at->format('Y-m-d') . 'T' . $att->created_at->format('h:i:s.u') . 'Z',
            'updated_at' => $att->updated_at->format('Y-m-d') . 'T' . $att->updated_at->format('h:i:s.u') . 'Z',
            'url' => $att->url,
            'url_inline' => $att->url_inline
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
}