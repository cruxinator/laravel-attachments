<?php

namespace Cruxinator\Attachments\Tests;

use Cruxinator\Attachments\Models\Attachment;
use Cruxinator\Attachments\Tests\Fixtures\Picture;
use Cruxinator\Attachments\Tests\Fixtures\User;
use Cruxinator\Attachments\Tests\Fixtures\UserNoAttachments;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Sftp\SftpAdapter;
use Mockery as m;
use org\bovigo\vfs\vfsStream;

class HasAttachmentsTest extends TestCase
{
    use DatabaseTransactions;

    public function testAttachNullToModel()
    {
        $foo = new User(['name' => 'name']);
        $this->assertTrue($foo->save());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Attached file is required');

        $foo->attachToModel(null);
    }

    public function suppliedProvider(): array
    {
        $result = [];
        $result['Key'] = ['key', 'putemhigh'];
        $result['Group'] = ['group', 'takemeaway'];
        $result['Type'] = ['type', Picture::class];

        return $result;
    }

    /**
     * @dataProvider suppliedProvider
     */
    public function testSuppliedOptions(string $field, string $value)
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
        File::shouldReceive('basename')->andReturn('PNG_transparency_demonstration_1.png');
        File::shouldReceive('copy')->andReturn(true);

        config(['attachments.attributes' => ['key', 'group', 'type']]);

        $foo = new User(['name' => 'name']);
        $this->assertTrue($foo->save());

        $upload = $file;
        $options = [$field => $value];
        if ('type' !== $field) {
            $options['type'] = Attachment::class;
        }

        $res = $foo->attachToModel($upload, $options);
        $this->assertEquals($value, $res->{$field});
    }

    public function testAttachmentMethod()
    {
        $foo = new User(['name' => 'name']);
        $this->assertTrue($foo->save());

        $att = new Attachment();
        $att->disk = 'local';
        $att->filepath = '';
        $att->filename = '';
        $att->filetype = '';
        $att->filesize = 0;
        $att->group = 'aybabtu';
        $att->attachable()->associate($foo);
        $att->save();

        $key = $att->refresh()->key;

        $nuAtt = $foo->attachment($key);
        $this->assertTrue($nuAtt instanceof Attachment);
        $this->assertEquals($att->getKey(), $nuAtt->getKey());

        $this->assertNull($foo->attachment('name'));
    }

    public function testAttachToModelFromStreamWithoutFilenameKabooms()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Attaching a resource requires options["filename"] to be set');

        $root = vfsStream::setup('shfl');
        $dir = vfsStream::url('shfl');

        $file = vfsStream::newFile('mosh.txt')->at($root)->setContent('MOSH MOSH MOSH, BAGS OF MONEY');
        $handle = fopen($file->url(), 'r');

        $foo = new User(['name' => 'name']);
        $this->assertTrue($foo->save());

        $res = $foo->attachToModel($handle);
    }

    public function testAttachToModelFromStreamOnNonLocalDisk()
    {
        $root = vfsStream::setup('shfl');
        $dir = vfsStream::url('shfl');

        $file = vfsStream::newFile('mosh.txt')->at($root)->setContent('MOSH MOSH MOSH, BAGS OF MONEY');
        $handle = fopen($file->url(), 'r');

        $mockDisk = m::mock(SftpAdapter::class);
        $mockDisk->expects('putStream')->andReturns(true);
        $mockDisk->expects('size')->andReturns(29);
        $mockDisk->expects('mimeType')->andReturns('text/plain');

        Storage::shouldReceive('disk')->withArgs(['s3'])->andReturn($mockDisk);

        $foo = new User(['name' => 'name']);
        $this->assertTrue($foo->save());

        $payload = [
            'filename' => 'mosh.txt',
            'disk' => 's3',
        ];

        $res = $foo->attachToModel($handle, $payload);
        $this->assertTrue($res instanceof Attachment);
    }

    public function testAttachToBadTarget()
    {
        $this->expectExceptionMessage('Supplied Model must use Cruxinator\Attachments\Traits\HasAttachments trait');

        $foo = new UserNoAttachments();

        Attachment::attach(null, $foo);
    }

    public function testAttachToModelWithUploadedFile()
    {
        $upload = UploadedFile::fake()->image('foobar.png');

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
        $oldId = $att->getKey();

        $options = ['key' => 'foobar'];
        
        $newAtt = $foo->attachToModel($upload, $options);
        $this->assertTrue($newAtt instanceof Attachment, get_class($newAtt));
        
        $this->assertNull(Attachment::find($oldId), "Old attachment with same key not flattened");
    }
}
