<?php


namespace Cruxinator\Attachments\Tests;

use Cruxinator\Attachments\Models\Attachment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class AttachmentFileHandlingTest extends TestCase
{
    use DatabaseTransactions;

    public function testOutput()
    {
        $att = new Attachment();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = 'lain-cyberia-mix.png';
        $att->filetype = 'image/png';
        $att->filesize = 1020;
        $att->attachable_type = '';
        $att->attachable_id = 0;
        $this->assertTrue($att->save());

        File::shouldReceive('get')->andReturn('foobar');

        $res = $att->output();
        $this->assertTrue($res instanceof Response);

        /** @var ResponseHeaderBag $headers */
        $headers = $res->headers;
        $this->assertEquals('image/png', $headers->get('Content-type'));
        $this->assertEquals(1020, $headers->get('Content-Length'));
        $this->assertEquals('bytes', $headers->get('Accept-Ranges'));
        $this->assertEquals('max-age=0, must-revalidate, no-cache, no-store, post-check=0, pre-check=0, private', $headers->get('Cache-Control'));
    }
}
