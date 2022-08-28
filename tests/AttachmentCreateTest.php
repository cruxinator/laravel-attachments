<?php


namespace Cruxinator\Attachments\Tests;

use Cruxinator\Attachments\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Mockery as m;

class AttachmentCreateTest extends TestCase
{
    use DatabaseTransactions;

    public function testFromPostNull()
    {
        $foo = new Attachment();

        $result = $foo->fromPost(null);
        $this->assertNull($result);
    }

    public function testFromPostNotNull()
    {
        $foo = new Attachment();

        $file = m::mock(UploadedFile::class)->makePartial();
        $file->allows('getSize')->andReturns(10240)->once();
        $file->allows('getMimeType')->andReturns('image/png')->once();
        $file->allows('getClientOriginalName')->andReturns('lain-cyberia-mix.png')->once();
        File::shouldReceive('copy')->andReturn(true)->once();
        File::shouldReceive('extension')->andReturn('png')->once();
        File::shouldReceive('isDirectory')->andReturn(true)->once();

        $result = $foo->fromPost($file);
        $this->assertNotNull($result);
        $this->assertEquals('local', $result->disk);
        $this->assertEquals(10240, $result->filesize);
        $this->assertEquals('image/png', $result->filetype);
        $this->assertEquals('lain-cyberia-mix.png', $result->filename);
    }

    public function testFromFileNull()
    {
        $foo = new Attachment();

        $res = $foo->fromFile(null);
        $this->assertNull($res);
    }

    public function testFromFileNotNull()
    {
        $foo = new Attachment();

        File::shouldReceive('size')->andReturn(10240);
        File::shouldReceive('basename')->andReturn('lain-cyberia-mix.png');
        File::shouldReceive('mimeType')->andReturn('image/png');
        File::shouldReceive('copy')->andReturn(true)->once();
        File::shouldReceive('extension')->andReturn('png')->once();
        File::shouldReceive('isDirectory')->andReturn(true)->once();
        $result = $foo->fromFile('lain-cyberia-mix.png');

        $this->assertNotNull($result);
        $this->assertEquals('local', $result->disk);
        $this->assertEquals(10240, $result->filesize);
        $this->assertEquals('image/png', $result->filetype);
        $this->assertEquals('lain-cyberia-mix.png', $result->filename);
    }

    public function testFromStreamNull()
    {
        $foo = new Attachment();

        $res = $foo->fromStream(null, 'lain-cyberia-mix.png');
        $this->assertNull($res);
    }
}