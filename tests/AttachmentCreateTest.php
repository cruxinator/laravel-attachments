<?php

namespace Cruxinator\Attachments\Tests;

use Cruxinator\Attachments\Models\Attachment;
use Cruxinator\Attachments\Tests\Fixtures\Document;
use Cruxinator\Attachments\Tests\Fixtures\AttachmentNoUuid;
use Cruxinator\Attachments\Tests\Fixtures\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Mockery as m;
use org\bovigo\vfs\vfsStream;

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

    public function testFromStreamLocal()
    {
        $root = vfsStream::setup('shfl');
        $dir = vfsStream::url('shfl');

        $file = vfsStream::newFile('mosh.txt')->at($root)->setContent('MOSH MOSH MOSH, BAGS OF MONEY');
        $handle = fopen($file->url(), 'r');

        $foo = new Attachment();
        $foo->disk = 'local';

        $ref = new \ReflectionClass(Attachment::class);
        $method = $ref->getMethod('isLocalStorage');
        $method->setAccessible(true);
        ;
        $this->assertTrue($method->invoke($foo));

        $res = $foo->fromStream($handle, 'mosh.txt');
        $this->assertTrue($res instanceof Attachment);
        $this->assertEquals('mosh.txt', $res->filename);
        $this->assertEquals($file->size(), $res->filesize);
        $this->assertEquals('text/plain', $res->filetype);
    }

    public function testPutStreamNoPathOffboard()
    {
        $root = vfsStream::setup('shfl');
        $dir = vfsStream::url('shfl');

        $file = vfsStream::newFile('mosh.txt')->at($root)->setContent('MOSH MOSH MOSH, BAGS OF MONEY');
        $handle = fopen($file->url(), 'r');

        Storage::shouldReceive('disk->putStream')->andReturn(true)->once();

        $foo = new Attachment();
        $foo->disk = 's3';
        $foo->filepath = 'foopath.txt';

        $ref = new \ReflectionClass(Attachment::class);
        $method = $ref->getMethod('isLocalStorage');
        $method->setAccessible(true);
        ;
        $this->assertFalse($method->invoke($foo));

        $res = $foo->putStream($handle);
        $this->assertTrue($res);
    }

    public function testPutStreamNoPathOnboard()
    {
        $root = vfsStream::setup('shfl');
        $dir = vfsStream::url('shfl');

        $file = vfsStream::newFile('mosh.txt')->at($root)->setContent('MOSH MOSH MOSH, BAGS OF MONEY');
        $handle = fopen($file->url(), 'r');

        $foo = new Document();
        $foo->disk = 'local';
        $foo->filepath = 'foopath.txt';

        $ref = new \ReflectionClass(Document::class);
        $method = $ref->getMethod('isLocalStorage');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke($foo));

        File::shouldReceive('isDirectory')->andReturn(false)->twice();
        File::shouldReceive('makeDirectory')->andReturn(false)->once();
        File::shouldReceive('put')->andReturn(true);

        $res = $foo->putStream($handle);
        $this->assertTrue($res);
    }

    public function testCreateWithBadProvider()
    {
        $this->expectExceptionMessage('Missing UUID provider configuration for attachments');

        config(['attachments.uuid_provider' => null]);

        $att = new Attachment();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = 'lain-cyberia-mix.png';
        $att->filetype = '';
        $att->filesize = 0;

        $att->save();
    }

    public function testCreateWithBadUUID()
    {
        $this->expectExceptionMessage('Failed to generate a UUID value');

        $att = new AttachmentNoUuid();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = 'lain-cyberia-mix.png';
        $att->filetype = '';
        $att->filesize = 0;
        $att->uuid = '';

        $att->save();
    }

    public function testAttachModelWithReplacementKey()
    {
        $foo = new User(['name' => 'name']);
        $this->assertTrue($foo->save());

        $att = new Attachment();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = '';
        $att->filetype = '';
        $att->filesize = 0;
        $att->key = 'foobar';
        $att->attachable()->associate($foo);
        $att->save();

        $nuAtt = new Attachment();
        $nuAtt->disk = 'local';
        $nuAtt->filepath = '';
        $nuAtt->filename = '';
        $nuAtt->filetype = '';
        $nuAtt->filesize = 0;
        $nuAtt->key = 'notfoobar';
        $nuAtt->save();
        $oldId = $att->getKey();

        $options = ['key' => 'foobar'];

        $res = Attachment::attach($nuAtt->uuid, $foo, $options);
        $this->assertNull(Attachment::find($oldId), "Old attachment with same key not flattened");
    }
}
